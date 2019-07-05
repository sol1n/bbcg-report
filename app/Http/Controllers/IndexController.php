<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Appercode\Backend;
use Appercode\User;
use Appercode\Form;
use Appercode\FormResponse;
use Appercode\Element;

class IndexController extends Controller
{
    private $user;

    public function __construct()
    {
        if (Cache::has('appercode-user')) {
            $this->user = Cache::get('appercode-user');
        } else {
            $token = config('appercode.token');
            $this->user = User::LoginByToken((new Backend), $token);
            Cache::put('appercode-user', $this->user, 20);
        }
    }

    private function getColors($key): array
    {
        $colors = ['rgba(200,0,0,0.4)', 'rgba(0,0,200,0.4)'];
        $colors2 = ['rgba(200,0,0,0.1)', 'rgba(0,0,200,0.1)'];

        if ($key < count($colors)) {
            return [
                'main' => $colors[$key],
                'background' => $colors[$key]
            ];
        }

        $random = 'rgba(' . rand(0, 255) . ',' . rand(0, 255) . ',' . rand(0,255) . ',';
        return [
            'main' => $random . '0.4)',
            'background' => $random . '0.1)'
        ];
    }

    private function getData($id = null)
    {
        $partLabels = [
            'Коллегиальность', 'Устремленность в будущее', 'Дистанция власти', 'Отношение к неопределенности', 'Жесткость', 'Цифровое поведение'
        ];

        $parts = Element::list('culturalCodeLegend', $this->user->backend, [
            'take' => -1,
            'order' => [
                'order' => 'asc'
            ]
        ])->map(function(Element $element) {
            return [
                'title' => $element->fields['title'] ?? '',
                'description' => $element->fields['description'] ?? '',
            ];
        });

        $scoreMap = [
            1 => 4,
            0 => 3,
            2 => 2,
            3 => 1
        ];

        $partMaps = [];

        $form = Form::list($this->user->backend, [
            'where' => [
                'id' => 'c050d07f-0270-49a7-bca3-e95017822523'
            ]
        ])->first();

        foreach ($form->parts as $key => $part) {
            $partMaps[$key] = [
                'label' => $parts[$key]['title'],
                'questions' => []
            ];

            foreach ($part['sections'][0]['groups'] as $group) {
                $partMaps[$key]['questions'][] = $group['controls'][0]['id'];
            }
        }

        $filter = [
            'where' => [
                'submittedAt' => [
                    '$exists' => true
                ]
            ]
        ];

        if (!is_null($id)) {
            $filter['where']['id'] = $id;
        }

        $responses = $form->responses($filter);

        $userIds = $responses->map(function(FormResponse $response) {
            return $response->userId;
        })->unique()->values()->toArray();

        if ($userIds) {
            $users = Element::list('UserProfiles', $this->user->backend, [
                'where' => [
                    'userId' => [
                        '$in' => $userIds
                    ]
                ]
            ])->mapWithKeys(function(Element $profile) {
                return ($profile->fields['lastName'] || $profile->fields['firstName'])
                    ? [$profile->fields['userId'] => $profile->fields['lastName'] . ' ' . $profile->fields['firstName']]
                    : [$profile->fields['userId'] => 'User ' . $profile->fields['userId']];
            })->toArray();
        } else {
            $users = [];
        }

        $datasets = [];

        $responses->each(function(FormResponse $response, $key) use (&$datasets, $partMaps, $scoreMap, $users) {
            $colors = $this->getColors($key);

            $dataset = [
                'label' => isset($users[$response->userId])
                    ? $users[$response->userId]
                    : 'User ' . $response->userId,
                'borderColor' => $colors['main'],
                'backgroundColor' => $colors['background'],
                'data' => [0,0,0,0,0,0]
            ];

            foreach ($partMaps as $partKey => $part) {
                foreach ($part['questions'] as $questionId) {
                    $answer = $response->response[$questionId] ?? 0;
                    $dataset['data'][$partKey] += $scoreMap[$answer];
                }
            }

            foreach ($dataset['data'] as $partIndex => &$value) {
                $value = round($value / count($partMaps[$partIndex]['questions']), 1);
            }

            $datasets[] = $dataset;
        });

        return [
            'chart' => [
                'labels' => $partLabels,
                'datasets' => $datasets
            ],
            'form' => $form,
            'parts' => $parts
        ];
    }

    public function index()
    {
        $data = $this->getData(request()->get('id'));
        return view('index', [
            'data' => $data['chart'],
            'title' => array_first($data['form']->title),
            'parts' => $data['parts']
        ]);
    }
}

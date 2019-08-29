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

    private $formId;

    private $exampleId;

    public function __construct()
    {
        if (Cache::has('appercode-user')) {
            $this->user = Cache::get('appercode-user');
        } else {
            $token = config('appercode.token');
            $this->user = User::LoginByToken((new Backend), $token);
            Cache::put('appercode-user', $this->user, 20);
        }

        $this->formId = 'c050d07f-0270-49a7-bca3-e95017822523';
        $this->exampleId = null;

        if (request()->has('formId')) {
            $this->formId = request()->get('formId');
        }

        if (request()->has('exampleId')) {
            $this->exampleId = request()->get('exampleId');
        }
    }

    private function getColors($key): array
    {
        $colors = ['rgba(247,116,36,0.4)', 'rgba(0,0,200,0.4)'];
        $colors2 = ['rgba(247,116,36,0.1)', 'rgba(0,0,200,0.1)'];

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
        $parts = Element::list('culturalCodeLegend', $this->user->backend, [
            'take' => -1,
            'order' => [
                'order' => 'asc'
            ],
            'where' => [
                'formId' => $this->formId
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
                'id' => $this->formId
            ]
        ])->first();

        foreach ($form->parts as $key => $part) {
            if ($key) {
                $partMaps[$key - 1] = [
                    'label' => $parts[$key - 1]['title'] ?? "Часть $key",
                    'questions' => []
                ];

                foreach ($part['sections'][0]['groups'] as $group) {
                    $partMaps[$key - 1]['questions'][] = $group['controls'][0]['id'];
                }
            }
        }

        $filter = [
            'where' => [
                'submittedAt' => [
                    '$exists' => true
                ]
            ]
        ];

        if (!is_null($id) or !is_null($this->exampleId)) {
            $ids = [];
            if (!is_null($id)) {
                $ids[] = $id;
            }
            if (!is_null($this->exampleId)) {
                $ids[] = $this->exampleId;
            }
            $filter['where']['id']['$in'] = $ids;
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
                    : [$profile->fields['userId'] => 'Ваш результат'];
            })->toArray();
        } else {
            $users = [];
        }

        $datasets = [];

        $responses->each(function(FormResponse $response, $key) use (&$datasets, $partMaps, $scoreMap, $users) {
            $colors = $this->getColors($key);

            $emptyData = array_fill(0, count($partMaps), 0);

            $dataset = [
                'label' => isset($users[$response->userId])
                    ? $users[$response->userId]
                    : 'Ваш результат',
                'borderColor' => $colors['main'],
                'backgroundColor' => $colors['background'],
                'data' => $emptyData
            ];

            foreach ($partMaps as $partKey => $part) {
                foreach ($part['questions'] as $questionId) {
                    $answer = $response->response[$questionId] ?? 0;
                    $dataset['data'][$partKey] += $scoreMap[$answer];
                }
            }

            foreach ($dataset['data'] as $partIndex => &$value) {
                $value = round($value / count($partMaps[$partIndex]['questions']) * 3, 1);
            }

            $datasets[] = $dataset;
        });

        return [
            'chart' => [
                'labels' => $parts->map(function($part) {
                    return $part['title'];
                })->toArray(),
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
            'parts' => $data['parts'],
            'resultsUrl' => config('app.url') . (request()->get('id') ? '?id=' . request()->get('id') : '')
        ]);
    }
}

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

    private function getData()
    {
        $partLabels = [
            'Коллегиальность', 'Устремленность в будущее', 'Дистанция власти', 'Отношение к неопределенности', 'Жесткость', 'Цифровое поведение'
        ];

        $colors = ['rgba(200,0,0,0.4)', 'rgba(0,0,200,0.4)'];
        $colors2 = ['rgba(200,0,0,0.1)', 'rgba(0,0,200,0.1)'];

        $scoreMap = [
            1 => 1,
            0 => 2,
            2 => 3,
            3 => 4
        ];

        $partMaps = [];

        $form = Form::list($this->user->backend, [
            'where' => [
                'id' => 'c050d07f-0270-49a7-bca3-e95017822523'
            ]
        ])->first();

        foreach ($form->parts as $key => $part) {
            $partMaps[$key] = [
                'label' => $partLabels[$key],
                'questions' => []
            ];

            foreach ($part['sections'][0]['groups'] as $group) {
                $partMaps[$key]['questions'][] = $group['controls'][0]['id'];
            }
        }

        $responses = $form->responses([
            'where' => [
                'submittedAt' => [
                    '$exists' => true
                ]
            ]
        ]);

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

        $responses->each(function(FormResponse $response, $key) use (&$datasets, $partMaps, $colors, $colors2, $scoreMap, $users) {
            $dataset = [
                'label' => isset($users[$response->userId])
                    ? $users[$response->userId]
                    : 'User ' . $response->userId,
                'borderColor' => $colors[$key],
                'backgroundColor' => $colors2[$key],
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
            'labels' => $partLabels,
            'datasets' => $datasets
        ];
    }

    public function index()
    {
        return view('index', [
            'data' => $this->getData(),
        ]);
    }
}

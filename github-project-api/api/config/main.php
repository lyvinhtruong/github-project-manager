<?php

$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-api',
    'basePath' => dirname(__DIR__),    
    'bootstrap' => ['log'],
    'modules' => [
        'v1' => [
            'basePath' => '@app/modules/v1',
            'class' => 'api\modules\v1\Module'
        ]
    ],
    'components' => [        
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => false,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => [
                        'v1/project'
                    ],
                    'tokens' => [
                        '{id}' => '<id:\\w+>',
                        '{name}' => '<name:[a-zA-Z0-9\/\\-]+>'
                    ],
                    'extraPatterns' => [
                        'GET get-by-name/{name}' => 'get-by-name',
                    ],
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => [
                        'v1/issue',
                    ],
                    'tokens' => [
                        '{github_issue_id}' => '<github_issue_id:\\w+>',
                        '{title}' => '<title:[a-zA-Z0-9\/\\-]+>',
                        '{project_id}' => '<project_id:\\w+>',
                        '{project_name}' => '<project_name:\\w+>',
                        '{status_label}' => '<status_label:\\w+>',
                    ],
                    'extraPatterns' => [
                        'POST find-and-create' => 'find-and-create',
                        'POST update-status' => 'update-status',
                        'POST update-type' => 'update-type',
                        'POST assign' => 'assign',
                        'POST estimate' => 'estimate',
                    ],
                ],
                // '<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
            ],        
        ]
    ],
    'params' => $params,
];

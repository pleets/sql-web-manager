<?php

return [
    'router' => [
        'routes' => [
        	'Utils' => [
        		'module' => 'Utils',
        		'controller' => '',
        		'view' => ''
        	]
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'HTTP404' => __DIR__ . '/../view/layout/404.phtml',
            'blank'   => __DIR__ . '/../../Auth/view/layout/blank.phtml',
        ],
    ],
];
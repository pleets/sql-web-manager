<?php

return [
    'router' => [
        'routes' => [
        	'Connections' => [
        		'module' => '',
        		'controller' => '',
        		'view' => 'index'
        	]
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'default'   => __DIR__ . '/../../Dashboard/view/layout/dashboard.phtml',
        ],
    ],
];
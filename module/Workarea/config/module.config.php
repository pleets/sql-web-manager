<?php

return [
    'router' => [
        'routes' => [
        	'Workarea' => [
        		'module' => 'Workarea',
        		'controller' => 'Start',
        		'view' => 'index'
        	]
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'default'	=> __DIR__ . '/../view/layout/workarea.phtml',
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'default'   => __DIR__ . '/../view/layout/workarea.phtml',
            'blank'     => __DIR__ . '/../../Auth/view/layout/blank.phtml'
        ],
        'view_map' => [
            'validation' => __DIR__ . '/../../Auth/view/error/validation.phtml',
        ],
    ],
];
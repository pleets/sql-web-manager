<?php

return [
    'router' => [
        'routes' => [
        	'Auth' => [
        		'module' => 'Auth',
        		'controller' => 'LogIn',
        		'view' => 'index'
        	]
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'default'	=> __DIR__ . '/../view/layout/layout.phtml',
        ],
    ],
];
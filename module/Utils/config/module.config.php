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
            'default'	=> __DIR__ . '/../view/layout/layout.phtml',
        ],
    ],
];
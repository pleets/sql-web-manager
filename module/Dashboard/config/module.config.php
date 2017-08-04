<?php

return [
    'router' => [
        'routes' => [
        	'Dashboard' => [
        		'module' => 'Dashboad',
        		'controller' => 'Start',
        		'view' => 'index'
        	]
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'default'	=> __DIR__ . '/../view/layout/dashboard.phtml',
        ],
    ],
];
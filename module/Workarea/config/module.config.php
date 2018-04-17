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
];
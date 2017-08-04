<?php

return [
    'router' => [
        'routes' => [
            'Catcher' => [
                'module' => 'Catcher',
                'controller' => 'Index',
                'view' => 'index'
            ]
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'default'    => dirname(__FILE__) . '/../view/layout/layout.phtml',
        ],
    ],
];

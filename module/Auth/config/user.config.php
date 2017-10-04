<?php

return [
	'project' => [
		'name' => 'SQLWebManager',
	],
	'mail' => [
		'checking' => [
			'enabled' => false,
			'from'    => 'admin@localhost.com'
		],
	],
	"authentication" => [
		"method"  => "_COOKIE",
		"key"     => "session_id",
		"gateway" => [
			"entity" => "SWM_USERS",
			"credentials" => [
				"username" => "USERNAME",
				"password" => "USER_PASSWORD"
			]
		]
	],
	"redirect" => "Dashboard"
];
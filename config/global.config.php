<?php

return [
    'project' => [
		'id'   => 'id6447896',              # Unique ID for the project
		'name' => 'SQLWebManager'           # The name of your project
    ],
	'mail' => [
		"noreply" => "",
		"host"    => ""
	],
	"authentication" => [
		"method"  => "_COOKIE",				# the method to store credentials (_COOKIE, _SESSION)
		"key"     => "session_id6448654",		# the key in the array to store credentials
	],
	"database" => [
	    /** TABLE PREFIX:
	     * Database prefix of tables, specifically here, the prefix of entity gateway above.
	     */
		"prefix" => "SWM"
	]
];
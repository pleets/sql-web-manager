<?php

if (!function_exists('ifdef'))
{
	function ifdef($value, Array $array)
	{
		$global = __DIR__  . '/../../../config/global.config.php';

		if (file_exists($global))
		{
			$key = array_shift($array);
			$in  = include $global;

			do
			{
				if (is_array($in))
				{
					if (array_key_exists($key, $in))
						$in = $in[$key];
					else
						return $value;
				}
				else
					return $value;

				$key = ($array) ? array_shift($array) : NULL;

				if (!$key)
					return $in;

			} while($key);
		}
		else
			return $value;
	}
}

return [
	'project' => [
		'name' => ifdef('PROJECT NAME', ["project", "name"]),			# The name of your project
	],
	'mail' => [
        /** CHECKING:
         * If checking is enabled, the registering process will require e-mail verification.
         * If checking is enabled, the user will log in after registering.
         */
		'checking' => [
			'enabled' => "N",
			'from'    => ifdef('noreply@example.com', ["mail", "noreply"])
		],
		"host" => ifdef('localhost', ["mail", "host"])
	],
	"authentication" => [
		"method"  => ifdef('_COOKIE', ["authentication", "method"]),		# the method to store credentials (_COOKIE, _SESSION)
		"key"     => ifdef('session_id', ["authentication", "key"]),		# the key in the array to store credentials
        /** AUTH TYPE:
         * db_table: get credentials from a table in a database
         * db_user:  get credentials from database users (database authentication)
         */
		"type"    => "db_table",
        /** DB GATEWAY:
         * Gateway is used to connect to database. It consists in an entity that connects to
         * a database and checks the specified credentials. Theses will checked only if the AUTH TYPE is db_table.
         */
		"gateway" => [
			"entity" => "USER",			# Table name (without prefix if exists)
	        /** CREDENTIALS:
	         * The field names of credentials in the table.
	         */
			"credentials" => [
				"username" => "USERNAME",
				"password" => "USER_PASSWORD"
			],

			// [TO DO] - Add validations for the following ...


	        /** TABLE INFO:
	         * Other information may be required for abstraction. If mail_checking is not enabled, your must define
	         * at least the id_field for the table.
	         */
			"table_info" => [
				"columns" => [
					"id_field"    => "USER_ID",				# often the primary key
					"state_field" => "USER_STATE_ID",		# required if mail_checking is enabled
					"email_field" => "EMAIL"				# required registration process
				],
				"column_values" => [
					"state_field" => [
						"pending_email" => 1,				# required if mail_checking is enabled
						"user_active"   => 2,				# required if mail_checking is enabled
					]
				]
			]


		],
	],
    /** AUTHORIZATION:
     *
     * If authorization is enabled, after user registering someone must authorize the first login of a user.
     * After that, the user will log in because it was authorized. Basically, the authorization consist in to
     * assign a role to the user.
     *
     * If authorization is not enabled, users will log in after registration process without a previous authorization.
     */
	"authorization" => [
		"enabled" => "N"
	],
	"database" => [
	    /** TABLE PREFIX:
	     * Database prefix of tables, specifically here, the prefix of entity gateway above.
	     */
		"prefix" => ifdef('APP', ["database", "prefix"])
	],
	"redirect" => "Workarea"			# the module that will be redirect to, after authentication
];
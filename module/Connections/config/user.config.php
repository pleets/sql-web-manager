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
	"authentication" => [
		"method"  => ifdef('_COOKIE', ["authentication", "method"]),		# the method to store credentials (_COOKIE, _SESSION)
		"key"     => ifdef('session_id', ["authentication", "key"]),		# the key in the array to store credentials
	],
	"database" => [
	    /** TABLE PREFIX:
	     * Database prefix of tables, specifically here, the prefix of entity gateway above.
	     */
		"prefix" => ifdef('APP', ["database", "prefix"])
	],
	"redirect" => "Auth"			# where users will be redirect to if the they aren't logged in
];
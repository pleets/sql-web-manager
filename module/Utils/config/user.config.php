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
		'name' => ifdef('PROTOTYPE4', ["project", "name"]),			# The name of your project
	],
];
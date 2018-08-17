<?php

namespace Auth\Model;

use Drone\Db\Entity;

class User extends Entity
{
	/**
	 * @var string
	 */
    public $TOKEN;

	/**
	 * @var date
	 */
    public $RECORD_DATE;

    public function __construct($data = [])
    {
		$config = include 'module/Auth/config/user.config.php';

        $username_str = $config["authentication"]["gateway"]["credentials"]["username"];
        $password_str = $config["authentication"]["gateway"]["credentials"]["password"];
        $state_field  = $config["authentication"]["gateway"]["table_info"]["columns"]["state_field"];
        $email_field  = $config["authentication"]["gateway"]["table_info"]["columns"]["email_field"];
        $id_field     = $config["authentication"]["gateway"]["table_info"]["columns"]["id_field"];

        foreach ([$id_field, $username_str, $password_str, $state_field, $email_field] as $field)
        {
            $this->{$field} = null;
        }

    	parent::__construct($data);

		$table  = $config["authentication"]["gateway"]["entity"];
		$prefix = $config["database"]["prefix"];

        $this->setTableName($prefix . "_" . $table);
    }
}
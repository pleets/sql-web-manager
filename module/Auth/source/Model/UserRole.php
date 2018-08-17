<?php

namespace Auth\Model;

use Drone\Db\Entity;

class UserRole extends Entity
{
	/**
	 * @var string
	 */
    public $USER_ID;

	/**
	 * @var integer
	 */
    public $ROLE_ID;

    public function __construct($data = [])
    {
        parent::__construct($data);

        $config = include 'module/Auth/config/user.config.php';
        $prefix = $config["database"]["prefix"];

        $this->setTableName($prefix . "_" . "USER_ROLE");
    }
}
<?php

namespace Auth\Model;

use Drone\Db\Entity;

class Role extends Entity
{
	/**
	 * @var integer
	 */
    public $ROLE_ID;

	/**
	 * @var string
	 */
    public $ROLE_NAME;

	/**
	 * @var string
	 */
    public $STATE;

	/**
	 * @var date
	 */
    public $RECORD_DATE;

    public function __construct($data = [])
    {
    	parent::__construct($data);

		$config = include 'module/Auth/config/user.config.php';
		$prefix = $config["database"]["prefix"];

        $this->setTableName($prefix . "_" . "ROLE");
    }
}
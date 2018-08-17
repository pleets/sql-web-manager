<?php

namespace Auth\Model;

use Drone\Db\Entity;

class Resource extends Entity
{
	/**
	 * @var integer
	 */
    public $RESOURCE_ID;

	/**
	 * @var string
	 */
    public $RESOURCE_NAME;

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

        $this->setTableName($prefix . "_" . "RESOURCE");
    }
}
<?php

namespace Auth\Model;

use Drone\Db\Entity;

class User extends Entity
{
	/**
	 * @var integer
	 */
    public $USER_ID;

	/**
	 * @var string
	 */
    public $USERNAME;

	/**
	 * @var integer
	 */
    public $USER_STATE_ID;

	/**
	 * @var integer
	 */
    public $ROLE_ID;

	/**
	 * @var string
	 */
    public $USER_PASSWORD;

	/**
	 * @var string
	 */
    public $EMAIL;

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
    	parent::__construct($data);
        $this->setTableName("SWM_USERS");
    }
}
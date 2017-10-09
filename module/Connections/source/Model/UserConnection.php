<?php

namespace Connections\Model;

use Drone\Db\Entity;

class UserConnection extends Entity
{
	/**
	* @var integer
	*/
	public $USER_CONN_ID;

	/**
	* @var integer
	*/
	public $USER_ID;

	/**
	* @var integer
	*/
	public $CONN_TYPE_ID;

	/**
	* @var string
	*/
	public $CONNECTION_NAME;

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
        $this->setTableName("SWM_USER_CONNECTIONS");
    }
}
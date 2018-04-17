<?php

namespace Workarea\Model;

use Drone\Db\Entity;

class UserConnectionDetails extends Entity
{
	/**
	* @var integer
	*/
	public $USER_CONN_ID;

	/**
	* @var integer
	*/
	public $CONN_IDENTI_ID;

	/**
	* @var string
	*/
	public $FIELD_VALUE;

    public function __construct($data = [])
    {
    	parent::__construct($data);
        $this->setTableName("SWM_USER_CONN_DETAILS");
    }
}
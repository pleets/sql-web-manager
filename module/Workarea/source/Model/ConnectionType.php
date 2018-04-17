<?php

namespace Workarea\Model;

use Drone\Db\Entity;

class ConnectionType extends Entity
{
	/**
	* @var integer
	*/
	public $CONN_TYPE_ID;

	/**
	* @var string
	*/
	public $CONN_TYPE_NAME;

    public function __construct($data = [])
    {
    	parent::__construct($data);
        $this->setTableName("SWM_CONNECTION_TYPES");
    }
}
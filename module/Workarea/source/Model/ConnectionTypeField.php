<?php

namespace Workarea\Model;

use Drone\Db\Entity;

class ConnectionTypeField extends Entity
{
	/**
	* @var integer
	*/
	public $CONN_TYPE_ID;

	/**
	* @var integer
	*/
	public $CONN_IDENTI_ID;

	/**
	* @var string
	*/
	public $FIELD_NAME;

	/**
	* @var string
	*/
	public $PLACEHOLDER;

    public function __construct($data = [])
    {
    	parent::__construct($data);
        $this->setTableName("SWM_CONN_TYPE_FIELDS");
    }
}
<?php

namespace Connections\Model;

use Drone\Db\Entity;

class Identifiers extends Entity
{
	/**
	* @var integer
	*/
	public $CONN_IDENTI_ID;

	/**
	* @var string
	*/
	public $CONN_IDENTI_NAME;

    public function __construct($data = [])
    {
    	parent::__construct($data);
        $this->setTableName("SWM_CONN_IDENTIFIERS");
    }
}
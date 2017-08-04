<?php

namespace Connections\Model;

use Drone\Db\TableGateway\TableGateway;

class Authentication extends TableGateway
{
    public function connect($dbconfig)
    {
        $db = $this->getDriver()->getDb();

        foreach ($dbconfig as $key => $value)
        {
        	switch ($key)
        	{
        		case 'dbhost':
        			$db->setDbhost($value);
        			break;
        		case 'dbuser':
        			$db->setDbuser($value);
        			break;
        		case 'dbpass':
        			$db->setDbpass($value);
        			break;
        		case 'dbname':
        			$db->setDbname($value);
        			break;
        		case 'dbchar':
        			$db->setDbChar($value);
        			break;
        	}
        }

        return $db->reconnect();
    }
}
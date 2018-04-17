<?php

namespace Auth\Model;

use Drone\Db\Driver\DriverAdapter;

class Authentication extends DriverAdapter
{
    /**
     * Database authentication
     *
     * @param string $user
     * @param string $pass
     *
     * @return boolean
     */
    public function authenticate($user, $pass)
    {
        $db = $this->getDb();

        $db->setDbuser($user);
        $db->setDbpass($pass);

        if ($db->isConnected())
            return $db->reconnect()
        else
            return $db->connect();
    }
}
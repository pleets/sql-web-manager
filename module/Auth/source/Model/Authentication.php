<?php

namespace Auth\Model;

use Drone\Db\Driver\DriverAdapter;

class Authentication extends DriverAdapter
{
    /**
     * Realiza la autenticación por base de datos
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

        return $db->reconnect();
    }
}
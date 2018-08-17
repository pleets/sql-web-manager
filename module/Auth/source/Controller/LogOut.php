<?php

namespace Auth\Controller;

use Drone\Mvc\AbstractionController;
use Drone\Network\Http;

class LogOut extends AbstractionController
{
    /**
     * Closes the user session
     *
     * @return null
     */
    public function close()
    {
        # STANDARD VALIDATIONS [check method]
        if (!$this->isGet())
        {
            $http = new Http();
            $http->writeStatus($http::HTTP_METHOD_NOT_ALLOWED);

            die('Error ' . $http::HTTP_METHOD_NOT_ALLOWED .' (' . $http->getStatusText($http::HTTP_METHOD_NOT_ALLOWED) . ')!!');
        }

        $config = include 'module/Auth/config/user.config.php';
        $method = $config["authentication"]["method"];
        $key    = $config["authentication"]["key"];

        switch ($method)
        {
            case '_COOKIE':
                if (array_key_exists($key, $_COOKIE) || !empty($_COOKIE[$key]))
                    setcookie($key, $_COOKIE[$key], time() - 1, '/');
                break;

            case '_SESSION':
                session_destroy();
                break;
        }

        header("location: " . $this->getBasePath() . "/public/Auth");
    }
}
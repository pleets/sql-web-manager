<?php

namespace Auth\Controller;

use Drone\Mvc\AbstractionController;

class LogOut extends AbstractionController
{
    /**
     * Delete the session
     *
     * @return null
     */
    public function close()
    {
        $config = include 'module/Auth/config/user.config.php';
        $method = $config["authentication"]["method"];
        $key    = $config["authentication"]["key"];

        session_destroy();

        if (array_key_exists($key, $_COOKIE) || !empty($_COOKIE[$key]))
            setcookie($key, $_COOKIE[$key], time() - 1, '/');

        header("location: " . $this->basePath . "/public/Auth");
    }
}
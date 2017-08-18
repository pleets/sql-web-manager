<?php

namespace Auth\Controller;

use Drone\Mvc\AbstractionController;

class LogOut extends AbstractionController
{
    /**
     * Deletes the user session
     *
     * @return null
     */
    public function close()
    {
        # STANDARD VALIDATIONS [check method]
        if (!$this->isGet())
            die('Error 405 (Method Not Allowed)!!');

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

        header("location: " . $this->basePath . "/public/Auth");
    }
}
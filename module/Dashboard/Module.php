<?php

namespace Dashboard;

use Drone\Mvc\AbstractionModule;
use Drone\Mvc\AbstractionController;

class Module extends AbstractionModule
{
	public function init(AbstractionController  $c)
	{
        # redirect to other side if $_COOKIE username exists
        if (!array_key_exists("session_id", $_COOKIE) || empty($_COOKIE["session_id"]))
            header("location: " . $c->basePath ."/public/". "Auth");
	}
}
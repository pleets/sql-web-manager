<?php

chdir(dirname(__DIR__));

// Set localtime zone
date_default_timezone_set("America/Bogota");

// Memory limit
ini_set("memory_limit","256M");

// Run application
require_once("vendor/autoload.php");

try
{
	$config = include "config/application.config.php";
	$mvc = new Drone\Mvc\Application($config);
	$mvc->run();
}
# to load only the error view
catch (Drone\Mvc\Exception\ViewNotFoundException $e)
{
    $mvc->getRouter()->setIdentifiers('Utils', 'Error', 'notFoundView');
    $mvc->getRouter()->run();
}
# to load the error template
catch (Drone\Mvc\Exception\PageNotFoundException $e)
{
    $mvc->getRouter()->setIdentifiers('Utils', 'Error', 'notFound');
    $mvc->getRouter()->run();
}
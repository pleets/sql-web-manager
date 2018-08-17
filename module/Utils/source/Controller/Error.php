<?php

namespace Utils\Controller;

use Drone\Mvc\AbstractionController;
use Drone\Network\Http;

class Error extends AbstractionController
{
	public function notFound()
	{
		$this->setShowView(false);
		$this->setLayout('HTTP404');

        $http = new Http();
        $http->writeStatus($http::HTTP_NOT_FOUND);

		return [];
	}

	public function notFoundView()
	{
		$this->setLayout('blank');
		//$this->setTerminal(true);

        $http = new Http();
        $http->writeStatus($http::HTTP_NOT_FOUND);

		return [];
	}
}
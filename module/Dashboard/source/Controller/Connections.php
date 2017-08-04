<?php

namespace Dashboard\Controller;

use Drone\Mvc\AbstractionController;
use Connections\Model\ConnectionType;
use Connections\Model\ConnectionTypesTable;
use Exception;

class Connections extends AbstractionController
{
    /**
     * @var EntityManager
     */
    private $connectionTypesEntity;

    /**
     * @return EntityManager
     */
    private function getConnectionTypesEntity()
    {
        if (!is_null($this->connectionTypesEntity))
            return $this->connectionTypesEntity;

        $this->connectionTypesEntity = new EntityManager(new TableGateway(new ConnectionType()));

        return $this->connectionTypesEntity;
    }

}
<?php

namespace Auth\Model;

use Drone\Db\TableGateway\TableGateway;
use Drone\Db\Entity;

class GatewayAdapter extends TableGateway
{
    /**
     * Constructor
     *
     * @return null
     */
    public function __construct(Entity $entity)
    {
        parent::__construct($entity, false);

        $this->getDriver()->getDb()->setDbUser($_COOKIE["username"]);
        $this->getDriver()->getDb()->setDbpass($_COOKIE["password"]);

        if (!$this->getDriver()->getDb()->connect())
        {
            $errors = $this->getDriver()->getDb()->getErrors();
            throw new \Exception(array_shift($errors));
        }
    }
}
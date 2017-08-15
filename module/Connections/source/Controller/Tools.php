<?php

namespace Connections\Controller;

use Drone\Mvc\AbstractionController;
use Drone\Debug\Catcher;
use Drone\Dom\Element\Form;
use Drone\Validator\FormValidator;
use Drone\Db\TableGateway\EntityAdapter;
use Utils\Model\Entity as EntityMd;
use Drone\Db\TableGateway\TableGateway;
use Auth\Model\User as UserModel;
use Connections\Model\Identifiers;
use Connections\Model\ConnectionType;
use Connections\Model\ConnectionTypeField;
use Connections\Model\UserConnection;
use Connections\Model\UserConnectionsTable;
use Connections\Model\UserConnectionDetails;
use Connections\Model\Authentication;
use Exception;

class Tools extends AbstractionController
{
    /**
     * @var integer
     */
    private $identity;

    /**
     * @var EntityAdapter
     */
    private $usersEntity;

    /**
     * @var EntityAdapter
     */
    private $identifiersEntity;

    /**
     * @var EntityAdapter
     */
    private $connectionTypesEntity;

    /**
     * @var EntityAdapter
     */
    private $connectionFieldsEntity;

    /**
     * @var EntityAdapter
     */
    private $userConnectionEntity;

    /**
     * @var EntityAdapter
     */
    private $userConnectionDetailsEntity;

    /**
     * @return integer
     */
    private function getIdentity()
    {
        $config = include 'module/Auth/config/user.config.php';
        $method = $config["authentication"]["method"];
        $key    = $config["authentication"]["key"];

        switch ($method)
        {
            case '_COOKIE':

                $user = $this->getUsersEntity()->select([
                    "USERNAME" => $_COOKIE[$key]
                ]);

                break;

            case '_SESSION':

                $user = $this->getUsersEntity()->select([
                    "USERNAME" => $_SESSION[$key]
                ]);

                break;
        }

        $user = array_shift($user);

        return $user->USER_ID;
    }

    /**
     * @return UsersEntity
     */
    private function getUsersEntity()
    {
        if (!is_null($this->usersEntity))
            return $this->usersEntity;

        $this->usersEntity = new EntityAdapter(new TableGateway(new UserModel()));

        return $this->usersEntity;
    }

    /**
     * @return UsersEntity
     */
    private function getIdentifiersEntity()
    {
        if (!is_null($this->identifiersEntity))
            return $this->identifiersEntity;

        $this->identifiersEntity = new EntityAdapter(new TableGateway(new Identifiers()));

        return $this->identifiersEntity;
    }

    /**
     * @return EntityAdapter
     */
    private function getConnectionTypesEntity()
    {
        if (!is_null($this->connectionTypesEntity))
            return $this->connectionTypesEntity;

        $this->connectionTypesEntity = new EntityAdapter(new TableGateway(new ConnectionType()));

        return $this->connectionTypesEntity;
    }

    /**
     * @return EntityAdapter
     */
    private function getConnectionFieldsEntity()
    {
        if (!is_null($this->connectionFieldsEntity))
            return $this->connectionFieldsEntity;

        $this->connectionFieldsEntity = new EntityAdapter(new TableGateway(new ConnectionTypeField()));

        return $this->connectionFieldsEntity;
    }

    /**
     * @return EntityAdapter
     */
    private function getUserConnectionEntity()
    {
        if (!is_null($this->userConnectionEntity))
            return $this->userConnectionEntity;

        $this->userConnectionEntity = new EntityAdapter(new UserConnectionsTable(new UserConnection()));

        return $this->userConnectionEntity;
    }

    /**
     * @return EntityAdapter
     */
    private function getUserConnectionDetailsEntity()
    {
        if (!is_null($this->userConnectionDetailsEntity))
            return $this->userConnectionDetailsEntity;

        $this->userConnectionDetailsEntity = new EntityAdapter(new TableGateway(new UserConnectionDetails()));

        return $this->userConnectionDetailsEntity;
    }

    /**
     * Tests a connection
     *
     * @return array
     */
    public function testConnection()
    {
        # data to send
        $data = [];

        # environment settings
        $post = $this->getPost();           # catch $_POST
        $this->setTerminal(true);           # set terminal

        # TRY-CATCH-BLOCK
        try {

            $idenfiers = $this->getIdentifiersEntity()->select([]);
            $dbconfig = [];

            if (array_key_exists('conn_id', $post))
            {
                $id = $post["conn_id"];

                $details = $this->getUserConnectionDetailsEntity()->select([
                    "USER_CONN_ID" => $id
                ]);

                foreach ($details as $field)
                {
                    foreach ($idenfiers as $identifier)
                    {
                        if ($field->CONN_IDENTI_ID == $identifier->CONN_IDENTI_ID)
                            $dbconfig[$identifier->CONN_IDENTI_NAME] = $field->FIELD_VALUE;
                    }
                }

            }
            else
            {
                $id = 0;

                foreach ($post['field'][$post["type"]] as $field_number => $field_value)
                {
                    foreach ($idenfiers as $identifier)
                    {
                        if ($field_number == $identifier->CONN_IDENTI_ID)
                            $dbconfig[$identifier->CONN_IDENTI_NAME] = $field_value;
                    }
                }
            }

            $entity = new EntityMd([]);
            $entity->setConnectionIdentifier("CONN" . $id);

            $driverAdapter = new \Drone\Db\Driver\DriverAdapter($dbconfig, false);
            $driverAdapter->getDb()->reconnect();

            $err = $driverAdapter->getDb()->getErrors();

            if (count($err))
                throw new Exception(array_shift($err), 300);

            # SUCCESS-MESSAGE
            $data["process"] = "success";
        }
        catch (Exception $e)
        {
            # ERROR-MESSAGE
            $data["process"] = ($e->getCode() != 300) ? "error" : "warning";
            $data["message"] = $e->getMessage();

            return $data;
        }

        return $data;
    }

    /**
     * Puts a worksheet
     *
     * @return array
     */
    public function worksheet()
    {
        # data to send
        $data = [];

        $this->setTerminal(true);           # set terminal
        $post = $this->getPost();           # catch $_POST

        $data["id"]   = $post["id"];
        $data["conn"] = $post["conn"];

        return $data;
    }

    /**
     * Executes a statement
     *
     * @return array
     */
    public function execute()
    {
        # data to send
        $data = [];

        # environment settings
        $post = $this->getPost();           # catch $_POST
        $this->setTerminal(true);           # set terminal

        # TRY-CATCH-BLOCK
        try {

            $id = $post["conn"];

            $connection = $this->getUserConnectionEntity()->select([
                "USER_CONN_ID" => $id
            ]);

            $connection = array_shift($connection);

            $details = $this->getUserConnectionDetailsEntity()->select([
                "USER_CONN_ID" => $id
            ]);

            $idenfiers = $this->getIdentifiersEntity()->select([]);

            $dbconfig = [];

            foreach ($details as $field)
            {
                foreach ($idenfiers as $identifier)
                {
                    if ($field->CONN_IDENTI_ID == $identifier->CONN_IDENTI_ID)
                        $dbconfig[$identifier->CONN_IDENTI_NAME] = $field->FIELD_VALUE;
                }
            }

            $entity = new EntityMd([]);
            $entity->setConnectionIdentifier("CONN" . $id);

            $driverAdapter = new \Drone\Db\Driver\DriverAdapter($dbconfig, false);
            $driverAdapter->getDb()->reconnect();

            $err = $driverAdapter->getDb()->getErrors();

            if (count($err))
                throw new Exception(array_shift($err), 300);

            $sql_text = $post["sql"];

            /*
             * SQL parsing
             */
            $sql_text = trim($sql_text);
            $pos = strpos($sql_text, ';');

            if ($pos !== false)
            {
                $end_stament = strstr($sql_text, ';');

                if ($end_stament == ';')
                    $sql_text = strstr($sql_text, ';', true);
            }



            $auth = $driverAdapter;

            $data["results"] = $auth->getDb()->execute($sql_text);

            $data["num_rows"]      = $auth->getDb()->getNumRows();
            $data["num_fields"]    = $auth->getDb()->getNumFields();
            $data["rows_affected"] = $auth->getDb()->getRowsAffected();

            $rows = $auth->getDb()->getArrayResult();

            $data["data"] = [];

            # data parsing
            foreach ($rows as $key => $row)
            {
                $data["data"][$key] = [];

                foreach ($row as $column => $value)
                {
                    if (gettype($value) == 'object')
                    {
                        if  (get_class($value) == 'OCI-Lob')
                            $data["data"][$key][$column] = $value->load();
                        else
                            $data["data"][$key][$column] = $value;
                    }
                    else {
                        $data["data"][$key][$column] = $value;
                    }
                }
            }

            # SUCCESS-MESSAGE
            $data["process"] = "success";
        }
        catch (Exception $e)
        {
            # ERROR-MESSAGE
            $data["process"] = ($e->getCode() != 300) ? "error" : "warning";
            $data["message"] = $e->getMessage();

            return $data;
        }

        return $data;
    }
}
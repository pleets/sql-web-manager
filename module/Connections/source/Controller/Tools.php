<?php

namespace Connections\Controller;

use Auth\Model\User as UserModel;
use Connections\Model\Authentication;
use Connections\Model\ConnectionType;
use Connections\Model\ConnectionTypeField;
use Connections\Model\Identifiers;
use Connections\Model\UserConnection;
use Connections\Model\UserConnectionsTable;
use Connections\Model\UserConnectionDetails;
use Drone\Db\TableGateway\EntityAdapter;
use Drone\Db\TableGateway\TableGateway;
use Drone\Dom\Element\Form;
use Drone\Mvc\AbstractionController;
use Drone\Network\Http;
use Drone\Validator\FormValidator;
use Utils\Model\Entity as EntityMd;

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

            # STANDARD VALIDATIONS [check method]
            if (!$this->isPost())
            {
                $http = new Http();
                $http->writeStatus($http::HTTP_METHOD_NOT_ALLOWED);

                die('Error ' . $http::HTTP_METHOD_NOT_ALLOWED .' (' . $http->getStatusText($http::HTTP_METHOD_NOT_ALLOWED) . ')!!');
            }

            $idenfiers = $this->getIdentifiersEntity()->select([]);
            $dbconfig = [];

            if (array_key_exists('conn_id', $post))
            {
                # STANDARD VALIDATIONS [check needed arguments]
                $needles = ['conn_id'];

                array_walk($needles, function(&$item) use ($post) {
                    if (!array_key_exists($item, $post))
                    {
                        $http = new Http();
                        $http->writeStatus($http::HTTP_BAD_REQUEST);

                        die('Error ' . $http::HTTP_BAD_REQUEST .' (' . $http->getStatusText($http::HTTP_BAD_REQUEST) . ')!!');
                    }
                });

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
                # STANDARD VALIDATIONS [check needed arguments]
                $needles = ['field', 'type'];

                array_walk($needles, function(&$item) use ($post) {
                    if (!array_key_exists($item, $post))
                    {
                        $http = new Http();
                        $http->writeStatus($http::HTTP_BAD_REQUEST);

                        die('Error ' . $http::HTTP_BAD_REQUEST .' (' . $http->getStatusText($http::HTTP_BAD_REQUEST) . ')!!');
                    }
                });

                $components = [
                    "attributes" => [
                        "field" => [
                            "required"  => true,
                        ],
                        "type" => [
                            "required"  => true,
                        ]
                    ],
                ];

                $options = [
                    "field" => [
                        "label" => "Value of connection parameter"
                    ],
                    "type" => [
                        "label"      => "Type of connection parameter"
                    ]
                ];

                $form = new Form($components);
                $form->fill($post);

                $validator = new FormValidator($form, $options);
                $validator->validate();

                $data["validator"] = $validator;

                # STANDARD VALIDATIONS [check argument constraints]
                if (!$validator->isValid())
                {
                    $data["messages"] = $validator->getMessages();
                    throw new \Drone\Exception\Exception("Form validation errors!");
                }

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

            try
            {
                $entity = new EntityMd([]);
                $entity->setConnectionIdentifier("CONN" . $id);

                $driverAdapter = new \Drone\Db\Driver\DriverAdapter($dbconfig, false);
                $driverAdapter->getDb()->connect();
            }
            catch (\Exception $e)
            {
                $err = $driverAdapter->getDb()->getErrors();

                # SUCCESS-MESSAGE
                $data["process"] = "error";
                $data["message"] = array_shift($err);

                return $data;
            }

            # SUCCESS-MESSAGE
            $data["process"] = "success";
        }
        catch (\Drone\Exception\Exception $e)
        {
            # ERROR-MESSAGE
            $data["process"] = "warning";
            $data["message"] = $e->getMessage();
        }
        catch (\Exception $e)
        {
            $file = str_replace('\\', '', __CLASS__);
            $storage = new \Drone\Exception\Storage("cache/$file.json");

            if (($errorCode = $storage->store($e)) === false)
            {
                $errors = $storage->getErrors();
                $this->handleErrors($errors, __METHOD__);
            }

            $data["code"]    = $errorCode;
            $data["message"] = $e->getMessage();

            $config = include 'config/application.config.php';
            $data["dev_mode"] = $config["environment"]["dev_mode"];

            # redirect view
            $this->setMethod('error');

            return $data;
        }
        /*
         * Extra information about errors!
         * keep in mind that some errors are not throwed, i.e. are not exceptions.
         */
        finally
        {
            $dbErrors = $this->getIdentifiersEntity()->getTableGateway()->getDriver()->getDb()->getErrors();
            $this->handleErrors($dbErrors, __METHOD__);
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
        # STANDARD VALIDATIONS [check method]
        if (!$this->isPost())
        {
            $http = new Http();
            $http->writeStatus($http::HTTP_METHOD_NOT_ALLOWED);

            die('Error ' . $http::HTTP_METHOD_NOT_ALLOWED .' (' . $http->getStatusText($http::HTTP_METHOD_NOT_ALLOWED) . ')!!');
        }

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

            # STANDARD VALIDATIONS [check method]
            if (!$this->isPost())
            {
                $http = new Http();
                $http->writeStatus($http::HTTP_METHOD_NOT_ALLOWED);

                die('Error ' . $http::HTTP_METHOD_NOT_ALLOWED .' (' . $http->getStatusText($http::HTTP_METHOD_NOT_ALLOWED) . ')!!');
            }

            # STANDARD VALIDATIONS [check needed arguments]
            $needles = ['conn'];

            array_walk($needles, function(&$item) use ($post) {
                if (!array_key_exists($item, $post))
                {
                    $http = new Http();
                    $http->writeStatus($http::HTTP_BAD_REQUEST);

                    die('Error ' . $http::HTTP_BAD_REQUEST .' (' . $http->getStatusText($http::HTTP_BAD_REQUEST) . ')!!');
                }
            });

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

            $sql_text = $post["sql"];

            /*
             * SQL parsing
             */
            $sql_text = trim($sql_text);

            if (empty($sql_text))
                throw new \Drone\Exception\Exception("Empty statement!");

            $pos = strpos($sql_text, ';');

            if ($pos !== false)
            {
                $end_stament = strstr($sql_text, ';');

                if ($end_stament == ';')
                    $sql_text = strstr($sql_text, ';', true);
            }

            try {

                $connError = false;

                $entity = new EntityMd([]);
                $entity->setConnectionIdentifier("CONN" . $id);

                $driverAdapter = new \Drone\Db\Driver\DriverAdapter($dbconfig, false);

                # start time to compute execution
                $startTime = microtime(true);

                $driverAdapter->getDb()->connect();

                $auth = $driverAdapter;

                $data["results"] = $auth->getDb()->execute($sql_text);
            }
            # encapsulate real connection error!
            catch (\Drone\Db\Driver\Exception\ConnectionException $e)
            {
                $connError = true;

                $file = str_replace('\\', '', __CLASS__);
                $storage = new \Drone\Exception\Storage("cache/$file.json");

                if (($errorCode = $storage->store($e)) === false)
                {
                    $errors = $storage->getErrors();
                    $this->handleErrors($errors, __METHOD__);
                }

                $data["code"]    = $errorCode;
                $data["message"] = "Could not connect to database!";

                # to identify development mode
                $config = include 'config/application.config.php';
                $data["dev_mode"] = $config["environment"]["dev_mode"];

                # redirect view
                $this->setMethod('error');
            }
            catch (\Exception $e)
            {
                $err = $driverAdapter->getDb()->getErrors();

                $error = (count($err)) ? array_shift($err) : $e->getMessage();

                # SUCCESS-MESSAGE
                $data["process"] = "error";
                $data["message"] = $error;

                return $data;
            }

            # end time to compute execution
            $endTime = microtime(true);
            $elapsed_time = $endTime - $startTime;

            $data["time"] = round($elapsed_time, 4);

            if (!$connError)
            {
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
        }
        catch (\Drone\Exception\Exception $e)
        {
            # ERROR-MESSAGE
            $data["process"] = "warning";
            $data["message"] = $e->getMessage();
        }
        catch (\Exception $e)
        {
            $file = str_replace('\\', '', __CLASS__);
            $storage = new \Drone\Exception\Storage("cache/$file.json");

            if (($errorCode = $storage->store($e)) === false)
            {
                $errors = $storage->getErrors();
                $this->handleErrors($errors, __METHOD__);
            }

            $data["code"]    = $errorCode;
            $data["message"] = $e->getMessage();

            $config = include 'config/application.config.php';
            $data["dev_mode"] = $config["environment"]["dev_mode"];

            # redirect view
            $this->setMethod('error');

            return $data;
        }
        /*
         * Extra information about errors!
         * keep in mind that some errors are not throwed, i.e. are not exceptions.
         */
        finally
        {
            if (!is_null($this->identifiersEntity))
            {
                $dbErrors = $this->getIdentifiersEntity()->getTableGateway()->getDriver()->getDb()->getErrors();
                $this->handleErrors($dbErrors, __METHOD__);
            }
        }

        return $data;
    }

    private function handleErrors(Array $errors, $method)
    {
        if (count($errors))
        {
            $errorInformation = "";

            foreach ($errors as $errno => $error)
            {
                $errorInformation .=
                    "<strong style='color: #a94442'>".
                        $method
                            . "</strong>: <span style='color: #e24f4c'>{$error}</span> \n<br />";
            }

            $hd = @fopen('cache/errors.txt', "a");

            if (!$hd || !@fwrite($hd, $errorInformation))
            {
                # error storing are not mandatory!
            }
            else
                @fclose($hd);

            $config = include 'config/application.config.php';
            $dev = $config["environment"]["dev_mode"];

            if ($dev)
                echo $errorInformation;
        }
    }
}
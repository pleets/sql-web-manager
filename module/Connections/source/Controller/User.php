<?php

namespace Connections\Controller;

use Drone\Mvc\AbstractionController;
use Drone\Dom\Element\Form;
use Drone\Validator\FormValidator;
use Drone\Db\TableGateway\EntityAdapter;
use Drone\Db\TableGateway\TableGateway;
use Drone\Network\Http;
use Auth\Model\User as UserModel;
use Connections\Model\ConnectionType;
use Connections\Model\ConnectionTypeField;
use Connections\Model\UserConnection;
use Connections\Model\UserConnectionsTable;
use Connections\Model\UserConnectionDetails;

class User extends AbstractionController
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
     * Lists all user connections
     *
     * @return array
     */
    public function listConnections()
    {
        # data to send
        $data = array();

        # environment settings
        $this->setTerminal(true);           # set terminal

        # TRY-CATCH-BLOCK
        try {

            # STANDARD VALIDATIONS [check method]
            if (!$this->isGet())
            {
                $http = new Http();
                $http->writeStatus($http::HTTP_METHOD_NOT_ALLOWED);

                die('Error ' . $http::HTTP_METHOD_NOT_ALLOWED .' (' . $http->getStatusText($http::HTTP_METHOD_NOT_ALLOWED) . ')!!');
            }

            $data["connections"] = $this->getUserConnectionEntity()->select([
                "USER_ID" => $this->getIdentity(),
                "STATE"   => "A"
            ]);

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

        return $data;
    }

    /**
     * Deletes a connection
     *
     * @return array
     */
    public function deleteConnection()
    {
        clearstatcache();
        session_write_close();

        # data to send
        $data = array();

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
            $needles = ['id'];

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
                    "id" => [
                        "required"  => true,
                        "type"      => "number"
                    ]
                ],
            ];

            $options = [
                "id" => [
                    "label" => "Id"
                ]
            ];

            $form = new Form($components);
            $form->fill($post);

            $validator = new FormValidator($form, $options);
            $validator->validate();

            $data["validator"] = $validator;

            # form validation
            if (!$validator->isValid())
            {
                $data["messages"] = $validator->getMessages();
                throw new \Drone\Exception\Exception("Form validation errors!", 300);
            }

            $connection = $this->getUserConnectionEntity()->select([
                "USER_CONN_ID" => $post["id"]
            ]);

            if (!count($connection))
                throw new \Exception("The Connection does not exists!");

            $connection = array_shift($connection);

            if ($connection->STATE == 'I')
                throw new \Drone\Exception\Exception("This connection is already deleted!", 300);

            $connection->exchangeArray([
                "STATE" =>  'I'
            ]);

            $this->getUserConnectionEntity()->update($connection, [
                "USER_CONN_ID" => $post["id"]
            ]);

            $data["id"] = $post["id"];

            # SUCCESS-MESSAGE
            $data["process"] = "success";
        }
        catch (\Drone\Exception\Exception $e)
        {
            # ERROR-MESSAGE
            $data["process"] = "warning";
            $data["message"] = $e->getMessage();
        }
        # encapsulate real connection error!
        catch (\Drone\Db\Driver\Exception\ConnectionException $e)
        {
            $file = str_replace('\\', '', __CLASS__);
            $storage = new \Drone\Exception\Storage("cache/$file.json");

            if (($errorCode = $storage->store($e)) === false)
            {
                $errors = $storage->getErrors();
                $this->handleErrors($errors, __METHOD__);
            }

            $data["code"]    = $errorCode;
            $data["message"] = "Could not connect to database!";

            # redirect view
            $this->setMethod('error');
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

            # redirect view
            $this->setMethod('error');

            return $data;
        }

        return $data;
    }

    /**
     * Adds a connection
     *
     * @return array
     */
    public function addConnection()
    {
        clearstatcache();
        session_write_close();

        # data to send
        $data = array();

        # environment settings
        $post = $this->getPost();           # catch $_POST
        $this->setTerminal(true);           # set terminal

        # TRY-CATCH-BLOCK
        try {

            if ($this->isGet())
            {
                $types = $data["types"] = $this->getConnectionTypesEntity()->select([]);

                $fields = $this->getConnectionFieldsEntity()->select([]);

                $fieldTypes = [];

                foreach ($types as $type)
                {
                    $fieldTypes[$type->CONN_TYPE_ID] = [];
                }

                foreach ($fields as $field)
                {
                    $fieldTypes[$field->CONN_TYPE_ID][$field->CONN_IDENTI_ID] = $field;
                }

                $data["fieldTypes"] = $fieldTypes;

                # SUCCESS-MESSAGE
                $data["process"] = "register-form";
            }
            else if ($this->isPost())
            {
                # STANDARD VALIDATIONS [check needed arguments]
                $needles = ['field', 'type', 'aliasname'];

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
                        "type" => [
                            "required"  => true,
                            "type"      => "number"
                        ],
                        "aliasname" => [
                            "required"  => true,
                            "type"      => "text",
                            "minlength" => 2,
                            "maxlength" => 100
                        ],
                        "field" => [
                            "required"  => false,
                            "type"      => "text",
                            "minlength" => 2,
                            "maxlength" => 50
                        ],
                    ],
                ];

                $options = [
                    "type" => [
                        "label"      => "Type"
                    ],
                    "aliasname" => [
                        "label"      => "Connection name",
                        "validators" => [
                            "Alnum"  => ["allowWhiteSpace" => true]
                        ],
                    ],
                    "field" => [
                        "label"      => "Connection Param"
                    ],
                ];

                $form = new Form($components);
                $form->fill($post);

                $validator = new FormValidator($form, $options);
                $validator->validate();

                $data["validator"] = $validator;

                # form validation
                if (!$validator->isValid())
                {
                    $data["messages"] = $validator->getMessages();
                    throw new \Drone\Exception\Exception("Form validation errors!", 300);
                }

                $this->getUserConnectionEntity()->getTableGateway()->getDriver()->getDb()->beginTransaction();

                $userConnection = new UserConnection();
                $user_conn_id = $this->getUserConnectionEntity()->getTableGateway()->getNextId();

                $userConnection->exchangeArray([
                    "USER_CONN_ID"    => $user_conn_id,
                    "USER_ID"         => $this->getIdentity(),
                    "CONN_TYPE_ID"    => $post["type"],
                    "CONNECTION_NAME" => $post["aliasname"],
                    "STATE"           =>  'A'
                ]);

                $this->getUserConnectionEntity()->insert($userConnection);

                foreach ($post['field'][$post["type"]] as $field_number => $field_value)
                {
                    $userconnectionDetails = new UserConnectionDetails();

                    $userconnectionDetails->exchangeArray([
                        "USER_CONN_ID"   => $user_conn_id,
                        "CONN_IDENTI_ID" => $field_number,
                        "FIELD_VALUE"    => $field_value
                    ]);

                    $this->getUserConnectionDetailsEntity()->insert($userconnectionDetails);
                }

                $this->getUserConnectionEntity()->getTableGateway()->getDriver()->getDb()->endTransaction();

                # SUCCESS-MESSAGE
                $data["process"] = "process-response";
            }
            else
            {
                $http = new Http();
                $http->writeStatus($http::HTTP_METHOD_NOT_ALLOWED);

                die('Error ' . $http::HTTP_METHOD_NOT_ALLOWED .' (' . $http->getStatusText($http::HTTP_METHOD_NOT_ALLOWED) . ')!!');
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

        return $data;
    }

    /**
     * Updates a connection
     *
     * @return array
     */
    public function editConnection()
    {
        clearstatcache();
        session_write_close();

        # data to send
        $data = array();

        # environment settings
        $post = $this->getPost();           # catch $_POST
        $get = $_GET;                       # catch $_GET
        $this->setTerminal(true);           # set terminal

        # TRY-CATCH-BLOCK
        try {

            if ($this->isGet())
            {
                # STANDARD VALIDATIONS [check needed arguments]
                $needles = ['id'];

                array_walk($needles, function(&$item) use ($get) {
                    if (!array_key_exists($item, $get))
                    {
                        $http = new Http();
                        $http->writeStatus($http::HTTP_BAD_REQUEST);

                        die('Error ' . $http::HTTP_BAD_REQUEST .' (' . $http->getStatusText($http::HTTP_BAD_REQUEST) . ')!!');
                    }
                });

                $types = $data["types"] = $this->getConnectionTypesEntity()->select([]);

                $fields = $this->getConnectionFieldsEntity()->select([]);

                $fieldTypes = [];

                foreach ($types as $type)
                {
                    $fieldTypes[$type->CONN_TYPE_ID] = [];
                }

                foreach ($fields as $field)
                {
                    $fieldTypes[$field->CONN_TYPE_ID][$field->CONN_IDENTI_ID] = $field;
                }

                $data["fieldTypes"] = $fieldTypes;

                $connection = $this->getUserConnectionEntity()->select([
                    "USER_CONN_ID" => $get["id"]
                ]);

                if (!count($connection))
                    throw new \Exception("The Connection does not exists!");

                $connection = array_shift($connection);

                if ($connection->STATE == 'I')
                    throw new \Drone\Exception\Exception("This connection was deleted!", 300);

                $connection_details = $this->getUserConnectionDetailsEntity()->select([
                    "USER_CONN_ID" => $get["id"]
                ]);

                $_connection_details = [];

                foreach ($connection_details as $details)
                {
                    $_connection_details[$details->CONN_IDENTI_ID] = $details;
                }

                $data["connection"] = $connection;
                $data["connection_details"] = $_connection_details;

                # SUCCESS-MESSAGE
                $data["process"] = "update-form";
            }
            else if ($this->isPost())
            {
                # STANDARD VALIDATIONS [check needed arguments]
                $needles = ['_conn_id', 'type', 'aliasname'];

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
                        "type" => [
                            "required"  => true,
                            "type"      => "number"
                        ],
                        "aliasname" => [
                            "required"  => true,
                            "type"      => "text",
                            "minlength" => 2,
                            "maxlength" => 100
                        ],
                        "field" => [
                            "required"  => false,
                            "type"      => "text",
                            "minlength" => 2,
                            "maxlength" => 50
                        ],
                    ],
                ];

                $options = [
                    "type" => [
                        "label"      => "Type"
                    ],
                    "aliasname" => [
                        "label"      => "Connection name",
                        "validators" => [
                            "Alnum"  => ["allowWhiteSpace" => true]
                        ],
                    ],
                    "field" => [
                        "label"      => "Connection Param",
                    ],
                ];

                $form = new Form($components);
                $form->fill($post);

                $validator = new FormValidator($form, $options);
                $validator->validate();

                $data["validator"] = $validator;

                # form validation
                if (!$validator->isValid())
                {
                    $data["messages"] = $validator->getMessages();
                    throw new \Drone\Exception\Exception("Form validation errors!");
                }

                $this->getUserConnectionEntity()->getTableGateway()->getDriver()->getDb()->beginTransaction();

                $userConnection = $this->getUserConnectionEntity()->select([
                    "USER_CONN_ID"    => $post["_conn_id"],
                ]);

                if (!count($userConnection))
                    throw new \Exception("The Connection does not exists!");

                $userConnection = array_shift($userConnection);

                if ($userConnection->STATE == 'I')
                    throw new \Drone\Exception\Exception("This connection was deleted!", 300);

                $userConnection->exchangeArray([
                    "CONN_TYPE_ID"    => $post["type"],
                    "CONNECTION_NAME" => $post["aliasname"]
                ]);

                $this->getUserConnectionEntity()->update($userConnection, [
                    "USER_CONN_ID"    => $post["_conn_id"],
                ]);

                $this->getUserConnectionDetailsEntity()->delete([
                    "USER_CONN_ID"   => $post["_conn_id"]
                ]);

                foreach ($post['field'][$post["type"]] as $field_number => $field_value)
                {
                    $userconnectionDetails = new UserConnectionDetails();

                    $userconnectionDetails->exchangeArray([
                        "USER_CONN_ID"   => $post["_conn_id"],
                        "CONN_IDENTI_ID" => $field_number,
                        "FIELD_VALUE"    => $field_value
                    ]);

                    $this->getUserConnectionDetailsEntity()->insert($userconnectionDetails);
                }

                $this->getUserConnectionEntity()->getTableGateway()->getDriver()->getDb()->endTransaction();

                # SUCCESS-MESSAGE
                $data["process"] = "process-response";
            }
            else
            {
                $http = new Http();
                $http->writeStatus($http::HTTP_METHOD_NOT_ALLOWED);

                die('Error ' . $http::HTTP_METHOD_NOT_ALLOWED .' (' . $http->getStatusText($http::HTTP_METHOD_NOT_ALLOWED) . ')!!');
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
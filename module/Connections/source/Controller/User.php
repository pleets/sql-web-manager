<?php

namespace Connections\Controller;

use Drone\Mvc\AbstractionController;
use Drone\Debug\Catcher;
use Drone\Dom\Element\Form;
use Drone\Validator\FormValidator;
use Drone\Db\TableGateway\EntityAdapter;
use Drone\Db\TableGateway\TableGateway;
use Auth\Model\User as UserModel;
use Connections\Model\ConnectionType;
use Connections\Model\ConnectionTypeField;
use Connections\Model\UserConnection;
use Connections\Model\UserConnectionsTable;
use Connections\Model\UserConnectionDetails;
use Exception;

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
        $post = $this->getPost();           # catch $_POST
        $this->setTerminal(true);           # set terminal

        # TRY-CATCH-BLOCK
        try {

            $data["connections"] = $this->getUserConnectionEntity()->select([
                "USER_ID" => $this->getIdentity()
            ]);

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
     * Adds a connection
     *
     * @return array
     */
    public function addConnection()
    {
        # data to send
        $data = array();

        # environment settings
        $post = $this->getPost();           # catch $_POST
        $this->setTerminal(true);           # set terminal

        # TRY-CATCH-BLOCK
        try {

            if (!$this->isPost())
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
            else
            {
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
                        /*"validators" => [
                            "Alnum"  => ["allowWhiteSpace" => false]
                        ],*/
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
                    throw new Exception("Form validation errors!", 300);
                }

                $this->getUserConnectionEntity()->getTableGateway()->getDriver()->getDb()->beginTransaction();

                $userConnection = new UserConnection();
                $user_conn_id = $this->getUserConnectionEntity()->getTableGateway()->getNextId();

                $userConnection->exchangeArray([
                    "USER_CONN_ID"    => $user_conn_id,
                    "USER_ID"         => $this->getIdentity(),
                    "CONN_TYPE_ID"    => $post["type"],
                    "CONNECTION_NAME" => $post["aliasname"]
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

        }
        catch (Exception $e)
        {
            # ERROR-MESSAGE
            $data["process"] = ($e->getCode() != 300) ? "error" : "warning";
            $data["message"] = $e->getMessage();

            if ($e->getCode() !== 300)
            {
                $c = new Catcher();
                $c->setOutput('cache/output.txt');
                $c->storeException($e);
            }

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
        # data to send
        $data = array();

        # environment settings
        $post = $this->getPost();           # catch $_POST
        $get = $_GET;                       # catch $_GET
        $this->setTerminal(true);           # set terminal

        # TRY-CATCH-BLOCK
        try {

            if (!$this->isPost())
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

                $connection = $this->getUserConnectionEntity()->select([
                    "USER_CONN_ID" => $get["id"]
                ]);

                $connection_details = $this->getUserConnectionDetailsEntity()->select([
                    "USER_CONN_ID" => $get["id"]
                ]);

                $_connection_details = [];

                foreach ($connection_details as $details)
                {
                    $_connection_details[$details->CONN_IDENTI_ID] = $details;
                }

                $data["connection"] = array_shift($connection);
                $data["connection_details"] = $_connection_details;

                # SUCCESS-MESSAGE
                $data["process"] = "update-form";
            }
            else
            {
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
                        /*"validators" => [
                            "Alnum"  => ["allowWhiteSpace" => false]
                        ],*/
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
                    throw new Exception("Form validation errors!", 300);
                }

                $this->getUserConnectionEntity()->getTableGateway()->getDriver()->getDb()->beginTransaction();

                $userConnection = $this->getUserConnectionEntity()->select([
                    "USER_CONN_ID"    => $post["conn_id"],
                ]);

                $userConnection = array_shift($userConnection);

                $userConnection->exchangeArray([
                    "CONN_TYPE_ID"    => $post["type"],
                    "CONNECTION_NAME" => $post["aliasname"]
                ]);

                $this->getUserConnectionEntity()->update($userConnection, [
                    "USER_CONN_ID"    => $post["conn_id"],
                ]);

                $this->getUserConnectionDetailsEntity()->delete([
                    "USER_CONN_ID"   => $post["conn_id"]
                ]);

                foreach ($post['field'][$post["type"]] as $field_number => $field_value)
                {
                    $userconnectionDetails = new UserConnectionDetails();

                    $userconnectionDetails->exchangeArray([
                        "USER_CONN_ID"   => $post["conn_id"],
                        "CONN_IDENTI_ID" => $field_number,
                        "FIELD_VALUE"    => $field_value
                    ]);

                    $this->getUserConnectionDetailsEntity()->insert($userconnectionDetails);
                }

                $this->getUserConnectionEntity()->getTableGateway()->getDriver()->getDb()->endTransaction();

                # SUCCESS-MESSAGE
                $data["process"] = "process-response";
            }

        }
        catch (Exception $e)
        {
            # ERROR-MESSAGE
            $data["process"] = ($e->getCode() != 300) ? "error" : "warning";
            $data["message"] = $e->getMessage();

            if ($e->getCode() !== 300)
            {
                $c = new Catcher();
                $c->setOutput('cache/output.txt');
                $c->storeException($e);
            }

            return $data;
        }

        return $data;
    }
}
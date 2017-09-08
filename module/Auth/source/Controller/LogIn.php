<?php

namespace Auth\Controller;

use Auth\Model\User;
use Drone\Db\TableGateway\EntityAdapter;
use Drone\Db\TableGateway\TableGateway;
use Drone\Dom\Element\Form;
use Drone\Mvc\AbstractionController;
use Drone\Network\Http;
use Drone\Validator\FormValidator;
use Zend\Crypt\Password\Bcrypt;

class LogIn extends AbstractionController
{
    /**
     * @var EntityAdapter
     */
    private $usersEntity;

    /**
     * @return EntityAdapter
     */
    private function getUsersEntity()
    {
        if (!is_null($this->usersEntity))
            return $this->usersEntity;

        $this->usersEntity = new EntityAdapter(new TableGateway(new User()));

        return $this->usersEntity;
    }

    /**
     * Checks user session and redirect to other module if exists any active session
     *
     * @return null
     */
    private function checkSession()
    {
        $config = include 'module/Auth/config/user.config.php';
        $method = $config["authentication"]["method"];
        $key    = $config["authentication"]["key"];

        switch ($method)
        {
            case '_COOKIE':

                if (array_key_exists($key, $_COOKIE) || !empty($_COOKIE[$key]))
                {
                    if (array_key_exists("CR_VAR_URL_REJECTED", $_SESSION) || !empty($_SESSION["CR_VAR_URL_REJECTED"]))
                        header("location: " . $_SESSION["CR_VAR_URL_REJECTED"]);
                    else
                        header("location: " . $this->basePath . "/public/" . $config["redirect"]);
                }

                break;

            case '_SESSION':

                if (array_key_exists($key, $_SESSION) || !empty($_SESSION[$key]))
                {
                    if (array_key_exists("CR_VAR_URL_REJECTED", $_SESSION) || !empty($_SESSION["CR_VAR_URL_REJECTED"]))
                        header("location: " . $_SESSION["CR_VAR_URL_REJECTED"]);
                    else
                        header("location: " . $this->basePath . "/public/" . $config["redirect"]);
                }

                break;
        }
    }

    /**
     * Shows login form
     *
     * @return array
     */
    public function index()
    {
        # STANDARD VALIDATIONS [check method]
        if (!$this->isGet())
        {
            $http = new Http();
            $http->writeStatus($http::HTTP_METHOD_NOT_ALLOWED);

            die('Error ' . $http::HTTP_METHOD_NOT_ALLOWED .' (' . $http->getStatusText($http::HTTP_METHOD_NOT_ALLOWED) . ')!!');
        }

        $this->checkSession();

        return [];
    }

    /**
     * Checks user credentials
     *
     * @return array
     */
    public function attemp()
    {
        # data to send
        $data = [];

        $post = $this->getPost();
        $this->setTerminal(true);

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
            $needles = ['username', 'password'];

            array_walk($needles, function(&$item) use ($post) {
                if (!array_key_exists($item, $post))
                {
                    $http = new Http();
                    $http->writeStatus($http::HTTP_BAD_REQUEST);

                    die('Error ' . $http::HTTP_BAD_REQUEST .' (' . $http->getStatusText($http::HTTP_BAD_REQUEST) . ')!!');
                }
            });

            $this->checkSession();

            $components = [
                "attributes" => [
                    "username" => [
                        "required"  => true,
                        "type"      => "text",
                        "minlength" => 4,
                        "maxlength" => 20
                    ],
                    "password" => [
                        "required"  => true,
                        "type"      => "text",
                        "minlength" => 4,
                        "maxlength" => 20
                    ]
                ],
            ];

            $options = [
                "username" => [
                    "label" => "Username",
                    "validators" => [
                        "Alnum"  => ["allowWhiteSpace" => false]
                    ]
                ],
                "password" => [
                    "label"      => "Password"
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

            $row = $this->getUsersEntity()->select([
                "USERNAME" => $post["username"]
            ]);

            if (!count($row))
                throw new \Drone\Exception\Exception("Username or password are incorrect");

            $user = array_shift($row);

            $securePass = $user->USER_PASSWORD;
            $password = $post["password"];

            if ($user->USER_STATE_ID == 1)
                throw new \Drone\Exception\Exception("User pending of email checking!");

            $bcrypt = new Bcrypt();

            if (!$bcrypt->verify($password, $securePass))
                throw new \Drone\Exception\Exception("Username or password are incorrect");

            $config = include 'module/Auth/config/user.config.php';
            $key    = $config["authentication"]["key"];
            $method = $config["authentication"]["method"];

            switch ($method)
            {
                case '_COOKIE':
                    setcookie($key, $user->USERNAME, time() + 2000000000, '/');
                    break;

                case '_SESSION':
                    $_SESSION[$key] = $user->USERNAME;
                    break;
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
            $dbErrors = $this->getUsersEntity()->getTableGateway()->getDriver()->getDb()->getErrors();
            $this->handleErrors($dbErrors, __METHOD__);
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
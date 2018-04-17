<?php

namespace Auth\Controller;

use Auth\Model\User;
use Auth\Model\UserTbl;
use Drone\Db\TableGateway\EntityAdapter;
use Drone\Debug\Catcher;
use Drone\Dom\Element\Form;
use Drone\Mvc\AbstractionController;
use Drone\Network\Http;
use Drone\Validator\FormValidator;
use Zend\Crypt\Password\Bcrypt;

class SingUp extends AbstractionController
{
    /**
     * @var UsersEntity
     */
    private $usersEntity;

    /**
     * @return UsersEntity
     */
    private function getUsersEntity()
    {
        if (!is_null($this->usersEntity))
            return $this->usersEntity;

        $this->usersEntity = new EntityAdapter(new UserTbl(new User()));

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
     * Shows register form
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
     * Does register process
     *
     * @return array
     */
    public function attemp()
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
            $needles = ['username', 'email', 'password', 'password_confirm'];

            array_walk($needles, function(&$item) use ($post) {
                if (!array_key_exists($item, $post))
                {
                    $http = new Http();
                    $http->writeStatus($http::HTTP_BAD_REQUEST);

                    die('Error ' . $http::HTTP_BAD_REQUEST .' (' . $http->getStatusText($http::HTTP_BAD_REQUEST) . ')!!');
                }
            });

            $this->checkSession();

            if ($post["password"] !== $post["password_confirm"])
                throw new \Drone\Exception\Exception("The password fields are different!", 300);

            $components = [
                "attributes" => [
                    "username" => [
                        "required"  => true,
                        "type"      => "text",
                        "minlength" => 4,
                        "maxlength" => 20
                    ],
                    "email" => [
                        "required"  => true,
                        "type"      => "text",
                        "minlength" => 4,
                        "maxlength" => 50
                    ],
                    "password" => [
                        "required"  => true,
                        "type"      => "text",
                        "minlength" => 4,
                        "maxlength" => 20
                    ],
                    "password_confirm" => [
                        "required"  => true,
                        "type"      => "text",
                        "minlength" => 4,
                        "maxlength" => 20
                    ]
                ],
            ];

            $options = [
                "username" => [
                    "label"      => "Username",
                    "validators" => [
                        "Alnum"  => ["allowWhiteSpace" => false]
                    ]
                ],
                "email" => [
                    "label"      => "Email"
                ],
                "password" => [
                    "label"      => "Password"
                ],
                "password_confirm" => [
                    "label"      => "Password confirmation"
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
                throw new \Drone\Exception\Exception("Form validation errors!", 300);
            }

            $row = $this->getUsersEntity()->select([
                "USERNAME" => $post["username"]
            ]);

            if (count($row))
                throw new \Drone\Exception\Exception("This username already exists!", 300);

            $bcrypt = new Bcrypt();
            $securePass = $bcrypt->create($post["password"]);

            $t = base64_encode(time() . uniqid());
            $token = substr($t, 0, 30);

            $this->getUsersEntity()->getTableGateway()->getDriver()->getDb()->beginTransaction();

            $config = include 'module/Auth/config/user.config.php';

            $user = new User();

            $user->exchangeArray([
                "USER_ID"       => $this->getUsersEntity()->getTableGateway()->getNextId(),
                "USER_STATE_ID" => ($config["mail"]["checking"]["enabled"]) ? 1 : 2,
                "USERNAME"      => $post["username"],
                "EMAIL"         => $post["email"],
                "TOKEN"         => $token,
                "USER_PASSWORD" => $securePass
            ]);

            $this->getUsersEntity()->insert($user);

            $link = $_SERVER["HTTP_HOST"] . $this->basePath . "/public/Auth/SingUp/verifyEmail/user/" . $post["username"] . "/token/" . $token;

            $data["mail"] = ($config["mail"]["checking"]["enabled"]) ? true : false;

            if ($config["mail"]["checking"]["enabled"])
            {
                $from = $config["mail"]["checking"]["from"];

                $headers  = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                $headers .= 'From: '. $from ."\r\n". 'X-Mailer: PHP/' . phpversion();

                if (!@mail(
                    $post["email"], "Email checking!",
                    "Your account has been registered!. Please click on the following link to confirm your account<br /><br />
                    <a href='$link'>$token</a>.",
                    $headers
                ))
                {
                    $this->getUsersEntity()->getTableGateway()->getDriver()->getDb()->rollback();
                    throw new \Exception("Error trying to send email checking. Try it again later!.");
                }
            }

            $this->getUsersEntity()->getTableGateway()->getDriver()->getDb()->endTransaction();

            $data["username"] = $post["username"];
            $data["email"] = $post["email"];

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
     * Does email checking
     *
     * @return array
     */
    public function verifyEmail()
    {
        # data to send
        $data = [];

        # TRY-CATCH-BLOCK
        try {

            # STANDARD VALIDATIONS [check method]
            if (!$this->isGet())
            {
                $http = new Http();
                $http->writeStatus($http::HTTP_METHOD_NOT_ALLOWED);

                die('Error ' . $http::HTTP_METHOD_NOT_ALLOWED .' (' . $http->getStatusText($http::HTTP_METHOD_NOT_ALLOWED) . ')!!');
            }

            # STANDARD VALIDATIONS [check needed arguments]
            $needles = ['token', 'user'];

            array_walk($needles, function($item) {
                if (!array_key_exists($item, $_GET))
                {
                    $http = new Http();
                    $http->writeStatus($http::HTTP_BAD_REQUEST);

                    die('Error ' . $http::HTTP_BAD_REQUEST .' (' . $http->getStatusText($http::HTTP_BAD_REQUEST) . ')!!');
                }
            });

            # catch arguments
            $token = $_GET["token"];
            $user  = $_GET["user"];

            $row = $this->getUsersEntity()->select([
                "USERNAME" => $user,
                "TOKEN"    => $token
            ]);

            if (!count($row))
                throw new \Drone\Exception\Exception("Token has expired or username does not exists!.");

            $user = array_shift($row);

            if ($user->USER_STATE_ID <> 1)
                throw new \Drone\Exception\Exception("This email address had verified before!.", 300);

            $user->USER_STATE_ID = 2;

            $this->getUsersEntity()->update($user, [
                "USER_ID" => $user->USER_ID
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
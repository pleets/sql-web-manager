<?php

namespace Auth\Controller;

use Auth\Model\User;
use Auth\Model\UserTbl;
use Drone\Db\TableGateway\EntityAdapter;
use Drone\Debug\Catcher;
use Drone\Dom\Element\Form;
use Drone\Mvc\AbstractionController;
use Drone\Network\Http;
use Drone\Pear\Mail;
use Drone\Validator\FormValidator;
use Zend\Crypt\Password\Bcrypt;

class SingUp extends AbstractionController
{
    use \Drone\Error\ErrorTrait;

    /**
     * @var UsersEntity
     */
    private $userAdapter;

    /**
     * @return UsersEntity
     */
    private function getUserAdapter()
    {
        if (!is_null($this->userAdapter))
            return $this->userAdapter;

        $this->userAdapter = new EntityAdapter(new UserTbl(new User()));

        return $this->userAdapter;
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

        $global_config = include 'config/global.config.php';

        /** URL TO REDIRECT:
         *
         * By default if there isn't a session active, it will be redirected to the module in the user config file ($config["redirect"]).
         * If a last URI requested exists, it will be redirecto to it.
         *
         * Other modules must have the following line of code inside init method to ensure last uri redirection.
         * $_SESSION["last_uri_" . $global_config["project"]["id"]] = $_SERVER["REQUEST_URI"];
         * It should be an unique session id for the app to prevent bad redirections with other projects.
         */
        if (array_key_exists("last_uri_" . $global_config["project"]["id"], $_SESSION) || !empty($_SESSION["last_uri_" . $global_config["project"]["id"]]))
            $location = $_SESSION["last_uri_" . $global_config["project"]["id"]];
        else
            $location = $this->getBasePath() . "/public/" . $config["redirect"];

        switch ($method)
        {
            case '_COOKIE':

                if (array_key_exists($key, $_COOKIE) || !empty($_COOKIE[$key]))
                    header("location: " . $location);

                break;

            case '_SESSION':

                if (array_key_exists($key, $_SESSION) || !empty($_SESSION[$key]))
                    header("location: " . $location);

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

            $config = include 'module/Auth/config/user.config.php';
            $username_str = $config["authentication"]["gateway"]["credentials"]["username"];
            $password_str = $config["authentication"]["gateway"]["credentials"]["password"];
            $state_field  = $config["authentication"]["gateway"]["table_info"]["columns"]["state_field"];
            $id_field     = $config["authentication"]["gateway"]["table_info"]["columns"]["id_field"];
            $email_field  = $config["authentication"]["gateway"]["table_info"]["columns"]["email_field"];

            $pending_state = $config["authentication"]["gateway"]["table_info"]["column_values"]["state_field"]["pending_email"];
            $active_state  = $config["authentication"]["gateway"]["table_info"]["column_values"]["state_field"]["user_active"];

            $rowset = $this->getUserAdapter()->select([
                $username_str => $post["username"]
            ]);

            if (count($rowset))
                throw new \Drone\Exception\Exception("This username already exists!", 300);

            $bcrypt = new Bcrypt();
            $securePass = $bcrypt->create($post["password"]);

            $t = base64_encode(time() . uniqid());
            $token = substr($t, 0, 30);

            $this->getUserAdapter()->getTableGateway()->getDriver()->getDb()->beginTransaction();

            $data["mail"] = ($config["mail"]["checking"]["enabled"] == "Y") ? true : false;

            $user = new User();

            $user->exchangeArray([
                $id_field     => $this->getUserAdapter()->getTableGateway()->getNextId(),
                $state_field  => $data["mail"] ? $pending_state : $active_state,
                $username_str => $post["username"],
                $email_field  => $post["email"],
                "TOKEN"       => $token,
                $password_str => $securePass
            ]);

            $this->getUserAdapter()->insert($user);

            $link = $_SERVER["REQUEST_SCHEME"] .'://'. $_SERVER["HTTP_HOST"] . $this->getBasePath() . "/public/Auth/SingUp/verifyEmail/user/" . $post["username"] . "/token/" . $token;

            if ($data["mail"])
            {
                $from = $config["mail"]["checking"]["from"];
                $host = $config["mail"]["host"];

                $mail = new Mail();
                $mail->setHost($host);
                $subject = $this->translator->translate("Email checking") . "!";
                $body = $this->translator->translate("Your account has been registered") . "!." .
                        $this->translator->translate("Please click on the following link to confirm your account") .
                            "<br /><br /><a href='$link'>$link</a>.";

                $success = $mail->send($from, $post["email"], $subject, $body);

                if (!$success)
                {
                    $errors = array_values($mail->getErrors());
                    $err = array_shift($errors);
                    $this->getUserAdapter()->getTableGateway()->getDriver()->getDb()->rollback();
                    throw new \Exception($err);
                }
            }

            $this->getUserAdapter()->getTableGateway()->getDriver()->getDb()->endTransaction();

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

            # stores the error code
            if (($errorCode = $storage->store($e)) === false)
            {
                $errors = $storage->getErrors();

                # if error storing is not possible, handle it (internal app error)
                $this->handleErrors($errors, __METHOD__);
            }

            # errors retrived by the use of ErrorTrait
            if (count($this->getErrors()))
                $this->handleErrors($this->getErrors(), __METHOD__);

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

            $config = include 'module/Auth/config/user.config.php';
            $username_str = $config["authentication"]["gateway"]["credentials"]["username"];
            $state_field  = $config["authentication"]["gateway"]["table_info"]["columns"]["state_field"];

            $pending_state = $config["authentication"]["gateway"]["table_info"]["column_values"]["state_field"]["pending_email"];
            $active_state  = $config["authentication"]["gateway"]["table_info"]["column_values"]["state_field"]["user_active"];

            # catch arguments
            $token = $_GET["token"];
            $user  = $_GET["user"];

            $row = $this->getUserAdapter()->select([
                $username_str => $user,
                "TOKEN"       => $token
            ]);

            if (!count($row))
                throw new \Drone\Exception\Exception("Token has expired or username does not exists!.");

            $user = array_shift($row);

            if ($user->{$state_field} <> $pending_state)
                throw new \Drone\Exception\Exception("This email address had verified before!.", 300);

            $user->exchangeArray([
                $state_field => $active_state
            ]);

            $this->getUserAdapter()->update($user, [
                $username_str => $user->{$username_str}
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

            # stores the error code
            if (($errorCode = $storage->store($e)) === false)
            {
                $errors = $storage->getErrors();

                # if error storing is not possible, handle it (internal app error)
                $this->handleErrors($errors, __METHOD__);
            }

            # errors retrived by the use of ErrorTrait
            if (count($this->getErrors()))
                $this->handleErrors($this->getErrors(), __METHOD__);

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
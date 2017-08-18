<?php

namespace Auth\Controller;

use Drone\Mvc\AbstractionController;
use Drone\Debug\Catcher;
use Drone\Dom\Element\Form;
use Drone\Validator\FormValidator;
use Drone\Db\TableGateway\EntityAdapter;
use Zend\Crypt\Password\Bcrypt;
use Auth\Model\User;
use Auth\Model\UserTbl;
use Exception;

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
     * @return string|null
     */
    private function runAuthentication()
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
            die('Error 405 (Method Not Allowed)!!');

        $this->runAuthentication();
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
                die('Error 405 (Method Not Allowed)!!');

            # STANDARD VALIDATIONS [check needed arguments]
            $needles = ['username', 'email', 'password', 'password_confirm'];

            array_walk($needles, function(&$item) use ($post) {
                if (!array_key_exists($item, $post))
                    die("Error 400 (Bad Request)!!");
            });

            # run authentication
            $this->runAuthentication();

            if ($post["password"] !== $post["password_confirm"])
                throw new Exception("The password fields are different!", 300);

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
                throw new Exception("Form validation errors!", 300);
            }

            $row = $this->getUsersEntity()->select([
                "USERNAME" => $post["username"]
            ]);

            if (count($row))
                throw new Exception("This username already exists!", 300);

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
                    throw new Exception("Error trying to send email checking. Try it again later!.");
                }
            }

            $this->getUsersEntity()->getTableGateway()->getDriver()->getDb()->endTransaction();

            $data["username"] = $post["username"];
            $data["email"] = $post["email"];

            # SUCCESS-MESSAGE
            $data["process"] = "success";
        }
        catch (Exception $e) {

            # ERROR-TRACKING
            $data["code"] = $e->getCode();
            $data["process"] = (in_array($e->getCode(), [300])) ? "warning" : "error";
            $data["message"] = $e->getMessage();

            if (!in_array($e->getCode(), [300]))
            {
                $c = new Catcher();
                $c->setOutput('cache/output.txt');

                if (($id = $c->storeException($e)) === false)
                {
                    $errors = $c->getErrors();
                    echo "<div style='color: red; font-weight: bold'>" .array_shift($errors). "</div><br />";
                }
            }

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
                die('Error 405 (Method Not Allowed)!!');

            # STANDARD VALIDATIONS [check needed arguments]
            $needles = ['token', 'user'];

            array_walk($needles, function(&$item) use ($_GET) {
                if (!array_key_exists($item, $_GET))
                    die("Error 400 (Bad Request)!!");
            });

            # catch arguments
            $token = $_GET["token"];
            $user  = $_GET["user"];

            $row = $this->getUsersEntity()->select([
                "USERNAME" => $user,
                "TOKEN"    => $token
            ]);

            if (!count($row))
                throw new Exception("Token has expired or username does not exists!.");

            $user = array_shift($row);

            if ($user->USER_STATE_ID <> 1)
                throw new Exception("This email address had verified before!.", 300);

            $user->USER_STATE_ID = 2;

            $this->getUsersEntity()->update($user, [
                "USER_ID" => $user->USER_ID
            ]);

            # SUCCESS-MESSAGE
            $data["process"] = "success";
        }
        catch (Exception $e) {

            # ERROR-MESSAGE
            $data["process"] = ($e->getCode() == 300) ? "warning": "error";
            $data["message"] = $e->getMessage();

            return $data;
        }

        return $data;
    }
}
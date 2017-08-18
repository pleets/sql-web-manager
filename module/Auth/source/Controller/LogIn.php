<?php

namespace Auth\Controller;

use Drone\Mvc\AbstractionController;
use Drone\Dom\Element\Form;
use Drone\Validator\FormValidator;
use Drone\Db\TableGateway\EntityAdapter;
use Drone\Db\TableGateway\TableGateway;
use Zend\Crypt\Password\Bcrypt;
use Auth\Model\User;
use Exception;

class LogIn extends AbstractionController
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

        $this->usersEntity = new EntityAdapter(new TableGateway(new User()));

        return $this->usersEntity;
    }

    /**
     * Checks user session and redirect to other module if exists any active session
     *
     * @return null
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
     * Shows login form
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
     * Checks user credentials
     *
     * @return array
     */
    public function attemp()
    {
        var_dump($_SERVER["REQUEST_METHOD"]);

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
            $needles = ['username', 'password'];

            array_walk($needles, function(&$item) use ($post) {
                if (!array_key_exists($item, $post))
                    die("Error 400 (Bad Request)!!");
            });

            $this->runAuthentication();

            $components = [
                "attributes" => [
                    "username" => [
                        "required" => true,
                        "type"  => "text",
                        "minlength" => 4,
                        "maxlength" => 20
                    ],
                    "password" => [
                        "required" => true,
                        "type"     => "text",
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
                throw new Exception("Form validation errors!", 300);
            }

            $row = $this->getUsersEntity()->select([
                "USERNAME" => $post["username"]
            ]);

            if (!count($row))
                throw new Exception("Username or password are incorrect", 300);

            $user = array_shift($row);

            $securePass = $user->USER_PASSWORD;
            $password = $post["password"];

            if ($user->USER_STATE_ID == 1)
                throw new Exception("User pending of email checking!", 300);

            $bcrypt = new Bcrypt();

            if (!$bcrypt->verify($password, $securePass))
                throw new Exception("Username or password are incorrect", 300);

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
        catch (Exception $e) {

            # ERROR-MESSAGE
            $data["process"] = ($e->getCode() == 300) ? "warning": "error";
            $data["message"] = $e->getMessage();

            return $data;
        }

        return $data;
    }
}
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
     * Shows login form
     *
     * @return array
     */
    public function index()
    {
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
        $this->runAuthentication();

        # data to send
        $data = [];

        # environment settings
        $post = $this->getPost();           # catch $_POST
        $this->setTerminal(true);           # set terminal

        # TRY-CATCH-BLOCK
        try {

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
                    "label"      => "Usuario",
                    "validators" => [
                        "Alnum"  => ["allowWhiteSpace" => false]
                    ]
                ],
                "password" => [
                    "label"      => "Contraseña"
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

            setcookie($key, $user->USERNAME, time() + 2000000000, '/');

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
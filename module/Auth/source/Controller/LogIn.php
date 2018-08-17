<?php

namespace Auth\Controller;

use Auth\Model\User;
use Auth\Model\UserRole;
use Auth\Model\DbUserRole;
use Auth\Model\Authentication;
use Drone\Db\TableGateway\EntityAdapter;
use Drone\Db\TableGateway\TableGateway;
use Drone\Dom\Element\Form;
use Drone\Mvc\AbstractionController;
use Drone\Network\Http;
use Drone\Validator\FormValidator;
use Zend\Crypt\Password\Bcrypt;
use Drone\Error\Errno;

class LogIn extends AbstractionController
{
    use \Drone\Error\ErrorTrait;

    /**
     * @var EntityAdapter
     */
    private $userAdapter;

    /**
     * @var EntityAdapter
     */
    private $userRoleAdapter;

    /**
     * @var EntityAdapter
     */
    private $dbUserRoleAdapter;

    /**
     * @return EntityAdapter
     */
    private function getUserAdapter()
    {
        if (!is_null($this->userAdapter))
            return $this->userAdapter;

        $this->userAdapter = new EntityAdapter(new TableGateway(new User()));

        return $this->userAdapter;
    }

    /**
     * @return EntityAdapter
     */
    private function getUserRoleAdapter()
    {
        if (!is_null($this->userRoleAdapter))
            return $this->userRoleAdapter;

        $this->userRoleAdapter = new EntityAdapter(new TableGateway(new UserRole()));

        return $this->userRoleAdapter;
    }

    /**
     * @return EntityAdapter
     */
    private function getDbUserRoleAdapter()
    {
        if (!is_null($this->dbUserRoleAdapter))
            return $this->dbUserRoleAdapter;

        $this->dbUserRoleAdapter = new EntityAdapter(new TableGateway(new DbUserRole()));

        return $this->dbUserRoleAdapter;
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

            $config = include 'module/Auth/config/user.config.php';

            $authorization = $config["authorization"];
            $username_str  = $config["authentication"]["gateway"]["credentials"]["username"];
            $password_str  = $config["authentication"]["gateway"]["credentials"]["password"];

            switch ($config["authentication"]["type"])
            {
                case 'db_user':

                    try
                    {
                        if ($authorization["enabled"])
                        {
                            $rowset = $this->getDbUserAdapter()->select([
                                $username_str => strtoupper($post["username"])
                            ]);

                            if (!count($rowset))
                                throw new \Drone\Exception\Exception("Your user is not authorized to use this application!");
                        }

                        $auth = new Authentication("default", false);
                        $result = $auth->authenticate($post["username"], $post["password"]);
                    }
                    catch (\Drone\Db\Driver\Exception\ConnectionException $e)
                    {
                        throw new \Drone\Exception\Exception("Wrong user or password");
                    }

                    break;

                case 'db_table':

                    $rowset = $this->getUserAdapter()->select([
                        $username_str => $post["username"]
                    ]);

                    if (!count($rowset))
                        throw new \Drone\Exception\Exception("Username or password are incorrect");

                    $user = array_shift($rowset);

                    if ($authorization["enabled"] == "Y")
                    {
                        $id_field = $config["authentication"]["gateway"]["table_info"]["columns"]["id_field"];

                        $rowset = $this->getUserRoleAdapter()->select([
                            $id_field => $user->{$id_field}
                        ]);

                        if (!count($rowset))
                            throw new \Drone\Exception\Exception("Your user is not authorized to use this application!");
                    }

                    $state_field = $config["authentication"]["gateway"]["table_info"]["columns"]["state_field"];
                    $state_pending_value = $config["authentication"]["gateway"]["table_info"]["column_values"]["state_field"]["pending_email"];

                    if ($user->{$state_field} == $state_pending_value)
                        throw new \Drone\Exception\Exception("User pending of email checking");

                    $securePass = $user->{$password_str};
                    $password = $post["password"];

                    $bcrypt = new Bcrypt();

                    if (!$bcrypt->verify($password, $securePass))
                        throw new \Drone\Exception\Exception("Username or password are incorrect");

                    break;

                default:
                    # code...
                    break;
            }

            $key    = $config["authentication"]["key"];
            $method = $config["authentication"]["method"];

            switch ($method)
            {
                case '_COOKIE':
                    setcookie($key, $post["username"], time() + 2000000000, '/');
                    break;

                case '_SESSION':
                    $_SESSION[$key] = $post["username"];
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
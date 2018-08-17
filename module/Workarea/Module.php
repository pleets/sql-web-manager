<?php

namespace Workarea;

use Auth\Model\User;
use Auth\Model\UserTbl;
use Drone\Db\TableGateway\EntityAdapter;
use Drone\Dom\Element\Form;
Use Drone\Mvc\AbstractionModule;
use Drone\Mvc\AbstractionController;
use Drone\Mvc\Layout;
use Drone\Validator\FormValidator;
use Drone\Util\ArrayDimension;

class Module extends AbstractionModule
{
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

    public function init(AbstractionController $c)
    {
        $config = $this->getUserConfig();

        $_config = ArrayDimension::toUnidimensional($config, "_");

        $this->setTranslator($c);

        $app_config = include 'config/application.config.php';
        $global_config = include 'config/global.config.php';

        /** LAST REQUESTED URI :
         * The last REQUEST_URI registered by $_SERVER. This session var is useful to redirect to the last URI requested
         * when users log in. It should be an unique session id for the app to prevent bad redirections with other projects.
         */
        # save only no XmlHttpRequest!
        if (!$c->isXmlHttpRequest())
            $_SESSION["last_uri_" . $global_config["project"]["id"]] = $_SERVER["REQUEST_URI"];

        # config constraints
        $components = [
            "attributes" => [
                "project_name" => [
                    "required"  => true,
                    "type"      => "text",
                    "minlength" => 2,
                    "maxlength" => 60
                ],
                "authentication_method" => [
                    "required"  => true,
                    "type"      => "text"
                ],
                "authentication_key" => [
                    "required"  => true,
                    "type"      => "text",
                    "minlength" => 1
                ],
                "database_prefix" => [
                    "required"  => false,
                    "type"      => "text"
                ],
                "redirect" => [
                    "required"  => true,
                    "type"      => "text"
                ]
            ],
        ];

        $options = [
            "project" => [
                "label"      => "project -> name"
            ],
            "authentication_method" => [
                "label"      => "authentication -> method",
                "validators" => [
                    "InArray"  => ["haystack" => ['_COOKIE', '_SESSION']]
                ]
            ],
            "authentication_key" => [
                "label"      => "authentication -> key",
            ],
            "database_prefix" => [
                "label"      => "database -> prefix"
            ],
            "redirect" => [
                "label"      => "redirect"
            ],
        ];

        $form = new Form($components);
        $form->fill($_config);

        $validator = new FormValidator($form, $options);
        $validator->validate();

        $data["validator"] = $validator;

        try
        {
            if (!$validator->isValid())
            {
                $data["messages"] = $validator->getMessages();
                throw new \Exception("Module config errros in user.config!", 300);
            }

            $redirect = $config["redirect"];
            $method   = $config["authentication"]["method"];
            $key      = $config["authentication"]["key"];

            $username_credential = null;

            switch ($method)
            {
                case '_COOKIE':

                    if (!array_key_exists($key, $_COOKIE) || empty($_COOKIE[$key]))
                    {
                        # stops current controller execution
                        $c->stopExecution(false);

                        header("location: " . $c->getBasePath() . "/public/" . $redirect);
                    }
                    else
                        $username_credential = $_COOKIE[$key];

                    break;

                case '_SESSION':

                    if (!array_key_exists($key, $_SESSION) || empty($_SESSION[$key]))
                    {
                        # stops current controller execution
                        $c->stopExecution(false);

                        header("location: " . $c->getBasePath() . "/public/" . $redirect);
                    }
                    else
                        $username_credential = $_SESSION[$key];

                    break;
            }

            # check inactivity (change user state to inactive while he's logged)
            $user = $this->getUserAdapter()->getTableGateway()->getUserByUsernameCredential($username_credential);

            $config = include 'module/Auth/config/user.config.php';
            $state_field  = $config["authentication"]["gateway"]["table_info"]["columns"]["state_field"];
            $active_state = $config["authentication"]["gateway"]["table_info"]["column_values"]["state_field"]["user_active"];

            if ($user->{$state_field} != $active_state)
                throw new \Exception("The user has been inactived!. Please log-in again.");
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
                //$this->handleErrors($errors, __METHOD__);
            }

            $data["code"]    = $errorCode;
            $data["message"] = $e->getMessage();

            $data["dev_mode"] = $app_config["environment"]["dev_mode"];

            # stops current controller execution
            $c->stopExecution(false);

            # loads error view
            $layoutManager = new Layout();
            $layoutManager->setBasePath($c->getBasePath());

            $layoutManager->setView($this, "validation");
            $layoutManager->setParams($data);

            # for AJAX requests!
            if ($c->isXmlHttpRequest())
                $layoutManager->content();
            else
                $layoutManager->fromTemplate($this, 'blank');
        }
    }

    private function setTranslator(AbstractionController $c)
    {
        $config = include('config/application.config.php');

        if (array_key_exists('locale', $_GET))
        {
            if (in_array($_GET['locale'], ['en', 'es', 'fr']))
                $_SESSION["LOCALE"] = $_GET['locale'];
        }

        $locale = (array_key_exists('LOCALE', $_SESSION)) ? $_SESSION["LOCALE"] : $config["environment"]["locale"];

        $i18nTranslator = \Zend\I18n\Translator\Translator::factory(
            [
                'locale'  => "$locale",
                'translation_files' => [
                    [
                        "type" => 'phparray',
                        "filename" => __DIR__ . "/lang/$locale.php"
                    ]
                ]
            ]
        );

        $c->translator = new \Zend\Mvc\I18n\Translator($i18nTranslator);
    }

    public function getUserConfig()
    {
        return include __DIR__ . "/config/user.config.php";
    }
}
<?php

namespace Workarea;

use Drone\Mvc\AbstractionModule;
use Drone\Mvc\AbstractionController;
use Drone\Dom\Element\Form;
use Drone\Validator\FormValidator;

class Module extends AbstractionModule
{
	public function init(AbstractionController  $c)
	{
		$config = $this->getUserConfig("Auth");
		$_config = $this->toFormConfig($config);

		# config constraints

        $components = [
            "attributes" => [
                "project_name" => [
                    "required"  => true,
                    "type"      => "text",
                    "minlength" => 2,
                    "maxlength" => 60
                ],
                "mail_checking_from" => [
                    "required"  => true,
                    "type"      => "email"
                ],
                "authentication_method" => [
                    "required"  => true,
                    "type"      => "text"
                ],
                "authentication_key" => [
                    "required"  => true,
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
            "mail_checking_from" => [
                "label"      => "mail -> checking -> from"
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
            "redirect" => [
                "label"      => "redirect"
            ],
        ];

        $form = new Form($components);
        $form->fill($_config);

        $validator = new FormValidator($form, $options);
        $validator->validate();

        $data["validator"] = $validator;

        # Validar formulario
        if (!$validator->isValid())
        {
            $data["messages"] = $validator->getMessages();
            throw new \Exception("Module config errros in user.config!", 300);
        }

        $method = $config["authentication"]["method"];
        $key    = $config["authentication"]["key"];

        switch ($method)
        {
            case '_COOKIE':

		        if (!array_key_exists($key, $_COOKIE) || empty($_COOKIE[$key]))
		            header("location: " . $c->basePath ."/public/". "Auth");

                break;

            case '_SESSION':

		        if (!array_key_exists($key, $_SESSION) || empty($_SESSION[$key]))
		            header("location: " . $c->basePath ."/public/". "Auth");

                break;
        }

        $this->setTranslator($c);
	}

    private function setTranslator(AbstractionController $c)
    {
        $config = include('config/application.config.php');
        $locale = $config["environment"]["locale"];

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

	public function getUserConfig($module)
	{
		return include __DIR__ . "/../$module/config/user.config.php";
	}

	private function toFormConfig($config)
	{
		$new_config = [];
		$again = false;

		foreach ($config as $param => $configure)
		{
			if (is_array($configure))
			{
				foreach ($configure as $key => $value)
				{
					$again = true;
					$new_config[$param . "_" . $key] = $value;
				}
			}
			else
				$new_config[$param] = $configure;
		}

		return (!$again) ? $new_config : $this->toFormConfig($new_config);
	}
}
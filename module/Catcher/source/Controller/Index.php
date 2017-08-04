<?php

namespace Catcher\Controller;

use Drone\Mvc\AbstractionController;
use Drone\FileSystem\Shell;

class Index extends AbstractionController
{
    public function index()
    {
        $data = [];

        $config = include 'module/Catcher/config/user.php';
        $folders = $config["output"];

        $shell = new shell();

        $_folders = [];

        foreach ($folders as $folder)
        {
            $files = $shell->ls($folder);

            $_files = [];

            foreach ($files as $file)
            {
                if (!in_array($file, ['.', '..']))
                    $_files[] = $file;
            }

            $_folders[$folder] = $_files;
        }

        $data["folders"] = $_folders;

        return $data;
    }

    public function read()
    {
        $data = [];

        $file = hex2bin($_GET["file"]);

        $json_object = (file_exists($file)) ? json_decode(file_get_contents($file)) : array();
        $data["json"] = $this->object_to_array($json_object);

        $data["file"] = $file;
        $data["_file"] = $_GET["file"];

        return $data;
    }

    public function readObject()
    {
        $data = [];

        $file = hex2bin($_GET["file"]);
        $key = $_GET["key"];

        $json_object = (file_exists($file)) ? json_decode(file_get_contents($file)) : array();
        $array_object = $this->object_to_array($json_object);

        $unserialized = unserialize($array_object[$key]["object"]);

        $data["file"] = $file;
        $data["key"] = $key;
        $data["object"] = $unserialized;

        return $data;
    }

    private function object_to_array($obj)
    {
        if (is_object($obj))
            $obj = (array) $obj;

        if (is_array($obj))
        {
            $new = array();

            foreach($obj as $key => $val)
            {
                $new[$key] = $this->object_to_array($val);
            }
        }
        else
            $new = $obj;

        return $new;
    }
}
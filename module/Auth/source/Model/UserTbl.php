<?php

namespace Auth\Model;

use Drone\Db\TableGateway\TableGateway;

class UserTbl extends TableGateway
{
    /**
     * Returns the next primary key in the table
     *
     * @return string
     */
    public function getNextId()
    {
        $table = $this->getEntity()->getTableName();

        $config = include 'module/Auth/config/user.config.php';
        $id_field  = $config["authentication"]["gateway"]["table_info"]["columns"]["id_field"];

        $sql = "SELECT CASE WHEN MAX($id_field) IS NULL THEN 1 ELSE MAX($id_field) + 1 END AS USER_ID FROM $table";

        $this->getDriver()->getDb()->execute($sql);
        $rowset = $this->getDriver()->getDb()->getArrayResult();
        $row = array_shift($rowset);

        return $row["USER_ID"];
    }

    /**
     * Returns the user by the id
     *
     * @param string $id
     *
     * @return User
     */
    public function getUserById($id)
    {
        $config = include 'module/Auth/config/user.config.php';
        $id_field  = $config["authentication"]["gateway"]["table_info"]["columns"]["id_field"];

        $rowset = $this->select([
            $id_field => $id
        ]);

        $row = array_shift($rowset);

        $filtered_array = array();

        foreach ($row as $key => $value)
        {
            if (is_string($key))
                $filtered_array[$key] = $value;
        }

        $user = new User();
        $user->exchangeArray($filtered_array);

        return $user;
    }

    /**
     * Returns the user by the username credential
     *
     * @param string $username
     *
     * @return User
     */
    public function getUserByUsernameCredential($username)
    {
        $config = include 'module/Auth/config/user.config.php';
        $username_credential  = $config["authentication"]["gateway"]["credentials"]["username"];

        $rowset = $this->select([
            $username_credential => $username
        ]);

        $row = array_shift($rowset);

        $filtered_array = array();

        foreach ($row as $key => $value)
        {
            if (is_string($key))
                $filtered_array[$key] = $value;
        }

        $user = new User();
        $user->exchangeArray($filtered_array);

        return $user;
    }
}
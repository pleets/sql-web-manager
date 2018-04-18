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

        $sql = "SELECT CASE WHEN MAX(USER_ID) IS NULL THEN 1 ELSE MAX(USER_ID) + 1 END USER_ID FROM $table";

        $this->getDriver()->getDb()->execute($sql);
        $rowset = $this->getDriver()->getDb()->getArrayResult();
        $row = array_shift($rowset);

        return $row["USER_ID"];
    }
}
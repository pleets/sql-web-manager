<?php

namespace Auth\Model;

use Drone\Db\TableGateway\TableGateway;

class RoleTbl extends TableGateway
{
    /**
     * Returns the next primary key in the table
     *
     * @return string
     */
    public function getNextId()
    {
        $table = $this->getEntity()->getTableName();

        $sql = "SELECT CASE WHEN MAX(ROLE_ID) IS NULL THEN 1 ELSE MAX(ROLE_ID) + 1 END AS ROLE_ID FROM $table";

        $this->getDriver()->getDb()->execute($sql);
        $rowset = $this->getDriver()->getDb()->getArrayResult();
        $row = array_shift($rowset);

        return $row["ROLE_ID"];
    }
}
<?php

namespace Auth\Model;

use Drone\Db\TableGateway\TableGateway;

class ResourceTbl extends TableGateway
{
    /**
     * Returns the next primary key in the table
     *
     * @return string
     */
    public function getNextId()
    {
        $table = $this->getEntity()->getTableName();

        $sql = "SELECT CASE WHEN MAX(RESOURCE_ID) IS NULL THEN 1 ELSE MAX(RESOURCE_ID) + 1 END AS RESOURCE_ID FROM $table";

        $this->getDriver()->getDb()->execute($sql);
        $rowset = $this->getDriver()->getDb()->getArrayResult();
        $row = array_shift($rowset);

        return $row["RESOURCE_ID"];
    }
}
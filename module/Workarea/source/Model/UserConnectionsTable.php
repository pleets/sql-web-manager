<?php

namespace Workarea\Model;

use Drone\Db\TableGateway\TableGateway;

class UserConnectionsTable extends TableGateway
{
    /**
     * Returns the max primary key to add
     *
     * @return string
     */
    public function getNextId()
    {
        $table = $this->getEntity()->getTableName();

        $sql = "SELECT MAX(USER_CONN_ID) USER_CONN_ID FROM $table";

        $this->getDriver()->getDb()->execute($sql);
        $rowset = $this->getDriver()->getDb()->getArrayResult();
        $row = array_shift($rowset);

        return is_null($row["USER_CONN_ID"]) ? 1 : (integer) $row["USER_CONN_ID"] + 1;
    }
}
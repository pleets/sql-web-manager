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
        $sql = "SELECT MAX(USER_ID) USER_ID FROM SWM_USERS";

        $this->getDriver()->getDb()->execute($sql);
        $rowset = $this->getDriver()->getDb()->getArrayResult();
        $row = array_shift($rowset);

        # [NOTE] - The sum $row["USER_ID"] + 1 could be truncated on 32-bit systems
        return is_null($row["USER_ID"]) ? 1 : $row["USER_ID"] + 1;
    }
}
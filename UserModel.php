<?php


namespace sigawa\mvccore;


use sigawa\mvccore\db\DbModel;

abstract class UserModel extends DbModel
{
    abstract public function getDisplayName(): string;
}

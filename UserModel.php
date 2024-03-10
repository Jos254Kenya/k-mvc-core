<?php


namespace sigawa\mvccore\core;


use sigawa\mvccore\core\db\DbModel;

abstract class UserModel extends DbModel
{
    abstract public function getDisplayName(): string;
}
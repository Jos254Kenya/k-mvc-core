<?php


namespace sigawa\mvc-core\core;


use sigawa\mvc-core\core\db\DbModel;

abstract class UserModel extends DbModel
{
    abstract public function getDisplayName(): string;
}
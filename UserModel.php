<?php


namespace Merudairy\Fmmerudairy\core;


use Merudairy\Fmmerudairy\core\db\DbModel;

abstract class UserModel extends DbModel
{
    abstract public function getDisplayName(): string;
}
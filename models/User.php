<?php

namespace sigawa\mvccore\models;

use sigawa\mvccore\db\DbModel;

class User extends DbModel
{
    public static function tableName(): string { return strtolower('User'); }
    public function attributes(): array { return []; }
    public function labels(): array { return []; }
    public function rules() { return []; }
}
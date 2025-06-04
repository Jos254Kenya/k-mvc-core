<?php

namespace sigawa\mvccore;

use sigawa\mvccore\db\DbModel;

abstract class UserModel extends DbModel
{
    public int $id;
    public ?string $session_token = null; // Allow null for unauthenticated users
    public ?string $role = 'user'; // Default role if not assigned
    public ?string $email = 'email@email.com'; // Default email if not assigned
    abstract public function getDisplayName(): string;
    abstract public function getPermissions(): array; // Each user can define their permissions
   
    public function toArray(): array
    {
        return get_object_vars($this); // Convert object properties to an array
    }
}

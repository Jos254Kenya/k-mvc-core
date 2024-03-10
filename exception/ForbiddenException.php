<?php


namespace sigawa\mvc-core\core\exception;


class ForbiddenException extends \Exception
{
    protected $message = 'You don\'t have permission to access this page';
    protected $code = 403;
}
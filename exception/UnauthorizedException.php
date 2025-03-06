<?php

namespace sigawa\mvccore\exception;

use Exception;

class UnauthorizedException extends Exception
{
    protected $message = 'Unauthorized access.';
    protected $code = 401; // HTTP 401 Unauthorized
}

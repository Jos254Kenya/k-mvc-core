<?php

namespace sigawa\mvccore\exception;

class NotFoundException extends \Exception
{
    /**
     * NotFoundException constructor.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = "Page not found", $code = 404, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

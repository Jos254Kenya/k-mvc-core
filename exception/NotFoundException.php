<?php

namespace sigawa\mvccore\exception;

/**
 * Class NotFoundException
 * Represents a 404 error when a requested resource is not found.
 */
class NotFoundException extends \Exception
{
    /**
     * NotFoundException constructor.
     *
     * @param string $message Custom error message (default: "Page not found").
     * @param int $code HTTP status code (default: 404).
     * @param \Throwable|null $previous Optional previous exception for exception chaining.
     */
    public function __construct(string $message = "Page not found", int $code = 404, ?\Throwable $previous = null)
    {
        // Call the parent constructor to initialize the exception.
        parent::__construct($message, $code, $previous);
    }
}

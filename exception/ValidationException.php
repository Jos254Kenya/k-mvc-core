<?php

namespace sigawa\mvccore\exception;

use Exception;

class ValidationException extends Exception {
    public array $errors;
    public string $fileLine;
    public string $trace;

    public function __construct(array $errors)
    {
        parent::__construct("Validation failed");
        $this->errors = $errors;

        // Automatically capture where the exception happened
        $this->fileLine = $this->getFile() . ':' . $this->getLine();
        $this->trace = $this->getTraceAsString();
    }

    public function toArray(): array
    {
        return [
            'error' => $this->getMessage(),
            'errors' => $this->errors,
            'file' => $this->fileLine,
            'trace' => $this->trace,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}

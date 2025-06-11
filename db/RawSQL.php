<?php 

namespace sigawa\mvccore\db;

/**
 * A wrapper for raw SQL expressions to be embedded directly in queries.
 */
class RawSQL
{
    public string $expression;

    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    public function __toString(): string
    {
        return $this->expression;
    }
}

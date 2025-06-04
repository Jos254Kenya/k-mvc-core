<?php

namespace McConsole\Handlers;

use McConsole\Handlers\ControllerHandler;
use McConsole\Handlers\ModelHandler;
use McConsole\Handlers\ServiceHandler;

class PackageHandler
{
    public static function handle(string $name, array $options = []): void
    {
        ModelHandler::handle($name);
        ControllerHandler::handle($name, $options);
        ServiceHandler::handle($name);
    }
}

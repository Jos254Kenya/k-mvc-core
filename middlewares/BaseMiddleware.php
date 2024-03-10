<?php


namespace sigawa\mvc-core\core\middlewares;


abstract class BaseMiddleware
{
    abstract public function execute();
}
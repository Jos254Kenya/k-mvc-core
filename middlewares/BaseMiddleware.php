<?php


namespace sigawa\mvccore\core\middlewares;


abstract class BaseMiddleware
{
    abstract public function execute();
}
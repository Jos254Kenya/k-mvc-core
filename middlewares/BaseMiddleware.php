<?php

namespace sigawa\mvccore\middlewares;

use sigawa\mvccore\Request;
use sigawa\mvccore\Response;

abstract class BaseMiddleware
{
    abstract public function execute(Request $request, Response $response);
}

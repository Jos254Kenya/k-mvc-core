<?php

namespace sigawa\mvccore\middlewares;

use sigawa\mvccore\Request;
use sigawa\mvccore\Response;

class CorsMiddleware extends BaseMiddleware
{
    public function execute(Request $request, Response $response)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        
        if ($request->getMethod() === 'OPTIONS') {
            $response->statusCode(403);
            exit();
        }
    }
}

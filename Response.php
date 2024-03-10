<?php


namespace Merudairy\Fmmerudairy\core;


class Response
{

    public function statusCode(int $code)
    {
        http_response_code($code);
    }

    public function redirect($url)
    {
        header("Location: $url");
    }
}
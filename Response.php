<?php

namespace sigawa\mvccore;

class Response
{
    public function statusCode(int $code)
    {
        http_response_code($code);
    }
    public function setHeader(string $name, string $value)
    {
        header("$name: $value");
    }
    public function redirect(string $url, int $statusCode = 302)
    {
        http_response_code($statusCode);
        header("Location: $url");
        exit();
    }
    public function json($data, int $statusCode = 200)
    {
        $this->statusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');
        echo json_encode($data);
        exit();
    }
    public function send(string $content, int $statusCode = 200)
    {
        $this->statusCode($statusCode);
        echo $content;
        exit();
    }
    public function setCookie(string $name, string $value, int $expire = 0, string $path = "", string $domain = "", bool $secure = false, bool $httponly = false)
    {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
    public function deleteCookie(string $name)
    {
        setcookie($name, '', time() - 3600);
    }
}

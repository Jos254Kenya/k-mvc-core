<?php


namespace sigawa\mvccore;


class Response
{

    public function statusCode(int $code)
    {
        http_response_code(response_code: $code);
    }

    public function redirect($url)
    {
        header("Location: $url");
    }
    public function json(array $data, int $statusCode = 200)
    {
        $this->statusCode($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit; // Ensure no further output is sent
    }

}

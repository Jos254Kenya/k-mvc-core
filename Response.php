<?php

namespace sigawa\mvccore;

class Response
{
    // Set the status code for the response
    public function statusCode(int $code)
    {
        http_response_code($code);
    }

    // Redirect to a specific URL
    public function redirect(string $url)
    {
        header("Location: $url");
        exit; // Ensure no further processing happens after redirect
    }

    // Send a JSON response
    public function json(array $data, int $statusCode = 200, array $headers = [])
    {
        $this->statusCode($statusCode);
        $this->setHeaders(array_merge(['Content-Type' => 'application/json'], $headers));
        echo json_encode($data);
        exit; // Ensure no further output is sent
    }

    // Send an HTML response
    public function html(string $htmlContent, int $statusCode = 200, array $headers = [])
    {
        $this->statusCode($statusCode);
        $this->setHeaders(array_merge(['Content-Type' => 'text/html; charset=UTF-8'], $headers));
        echo $htmlContent;
        exit; // Ensure no further output is sent
    }

    // Send an XML response
    public function xml(array $data, int $statusCode = 200, array $headers = [])
    {
        $this->statusCode($statusCode);
        $this->setHeaders(array_merge(['Content-Type' => 'application/xml; charset=UTF-8'], $headers));
        
        $xml = new \SimpleXMLElement('<response/>');
        $this->arrayToXml($data, $xml);
        echo $xml->asXML();
        exit; // Ensure no further output is sent
    }

    // Send a custom header
    public function header(string $name, string $value)
    {
        header("$name: $value");
    }

    // Set multiple headers
    public function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
    }

    // Helper function to convert array to XML
    private function arrayToXml(array $data, \SimpleXMLElement $xml)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subNode = $xml->addChild($key);
                $this->arrayToXml($value, $subNode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }

    // Predefined HTTP Status Code Responses

    public function ok(array $data = [])
    {
        return $this->json($data, 200);
    }

    public function created(array $data = [])
    {
        return $this->json($data, 201);
    }

    public function badRequest(array $data = [])
    {
        return $this->json($data, 400);
    }

    public function unauthorized(array $data = [])
    {
        return $this->json($data, 401);
    }

    public function notFound(array $data = [])
    {
        return $this->json($data, 404);
    }

    public function internalServerError(array $data = [])
    {
        return $this->json($data, 500);
    }

    // Handle file download
    public function download(string $filePath, string $fileName = null)
    {
        if (!file_exists($filePath)) {
            $this->notFound(["message" => "File not found"]);
        }

        $fileName = $fileName ?? basename($filePath);
        $this->setHeaders([
            'Content-Description' => 'File Transfer',
            'Content-Type' => mime_content_type($filePath),
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => filesize($filePath),
            'Cache-Control' => 'must-revalidate',
            'Pragma' => 'public',
        ]);

        readfile($filePath);
        exit;
    }

    // Add CORS headers
    public function cors(string $allowedOrigin = '*', string $allowedMethods = 'GET, POST, PUT, DELETE, OPTIONS', string $allowedHeaders = 'Content-Type, Authorization')
    {
        $this->setHeaders([
            'Access-Control-Allow-Origin' => $allowedOrigin,
            'Access-Control-Allow-Methods' => $allowedMethods,
            'Access-Control-Allow-Headers' => $allowedHeaders,
        ]);
    }

    // Add cache control
    public function cache(string $cacheControl, ?string $expires = null)
    {
        $this->header('Cache-Control', $cacheControl);
        if ($expires) {
            $this->header('Expires', $expires);
        }
    }
}

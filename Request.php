<?php

namespace sigawa\mvccore;

class Request
{
    private array $routeParams = [];
    private ?string $cachedMethod = null;
    private ?string $cachedUrl = null;

    public function getMethod(): string
    {
        if ($this->cachedMethod === null) {
            $this->cachedMethod = strtolower($_SERVER['REQUEST_METHOD'] ?? 'get');
        }
        return $this->cachedMethod;
    }

    public function getUrl(): string
    {
        if ($this->cachedUrl === null) {
            $url = $_SERVER['REQUEST_URI'] ?? '/';
            $position = strpos($url, '?');
            $this->cachedUrl = $position !== false ? substr($url, 0, $position) : $url;
        }
        return $this->cachedUrl;
    }
    public function setUrl(string $url): void
    {
        $this->cachedUrl = $url;
    }
    public function getBody(): array
    {
        $data = [];

        if ($this->isJson()) {
            $data = $this->getJsonBody();
        } elseif ($this->isGet()) {
            foreach ($_GET as $key => $value) {
                $data[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        } elseif ($this->isPost()) {
            foreach ($_POST as $key => $value) {
                $data[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        return $data;
    }

    public function getJsonBody(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON body');
        }
        return $data;
    }

    public function isJson(): bool
    {
        return isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json');
    }

    public function getParam(string $key, $default = null)
    {
        if ($this->isGet()) {
            return $_GET[$key] ?? $default;
        }
        if ($this->isPost()) {
            return $_POST[$key] ?? $default;
        }
        return $default;
    }
    public function isGet(): bool
    {
        return $this->getMethod() === 'get';
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'post';
    }

    

    public function getIntParam(string $key, $default = null): ?int
    {
        $value = $this->getParam($key, $default);
        return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : $default;
    }

    public function getFloatParam(string $key, $default = null): ?float
    {
        $value = $this->getParam($key, $default);
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false ? (float)$value : $default;
    }

    public function setRouteParams(array $params): self
    {
        $this->routeParams = $params;
        return $this;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function getRouteParam(string $param, $default = null)
    {
        return $this->routeParams[$param] ?? $default;
    }
    public function isPut(): bool
    {
        return $this->getMethod() === 'put';
    }
    public function isPatch(): bool
    {
        return $this->getMethod() === 'patch';
    }

    public function isDelete(): bool
    {
        return $this->getMethod() === 'delete';
    }
    public function getHeader(string $headerName): ?string
    {
        $headerName = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));
        return $_SERVER[$headerName] ?? null;
    }

    public function getAllHeaders(): array
    {
        return getallheaders() ?: [];
    }

    public function getUploadedFile(string $fieldName): ?array
    {
        return $_FILES[$fieldName] ?? null;
    }

    public function validateUploadedFile(string $fieldName, array $allowedTypes, int $maxSize): void
    {
        $file = $this->getUploadedFile($fieldName);

        if (!$file) {
            throw new \InvalidArgumentException("No file uploaded for field: $fieldName");
        }

        if (!in_array($file['type'], $allowedTypes, true)) {
            throw new \InvalidArgumentException("Invalid file type for field: $fieldName");
        }

        if ($file['size'] > $maxSize) {
            throw new \InvalidArgumentException("File exceeds maximum size for field: $fieldName");
        }
    }

    public function getQueryParams(): array
    {
        return $_GET ?? [];
    }

    public function hasParam(string $key): bool
    {
        return isset($_GET[$key]) || isset($_POST[$key]);
    }
}

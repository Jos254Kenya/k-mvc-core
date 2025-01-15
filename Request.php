<?php

namespace sigawa\mvccore;

class Request
{
    private array $routeParams = [];

    public function getMethod(): string
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public function getJsonBody(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        return json_last_error() === JSON_ERROR_NONE ? $data : [];
    }

    public function getPath(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        return $position !== false ? substr($path, 0, $position) : $path;
    }

    public function isGet(): bool
    {
        return $this->getMethod() === 'get';
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'post';
    }

    public function getBody(): array
    {
        $data = [];
        if ($this->isGet()) {
            foreach ($_GET as $key => $value) {
                $data[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        if ($this->isPost()) {
            foreach ($_POST as $key => $value) {
                $data[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        return $data;
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

    public function getParam(string $key, $default = null)
    {
        $value = $this->isGet() ? $_GET[$key] ?? null : ($_POST[$key] ?? null);
        return $value !== null ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $default;
    }

    public function hasParam(string $key): bool
    {
        return isset($_GET[$key]) || isset($_POST[$key]);
    }
}

<?php

namespace sigawa\mvccore;

use sigawa\mvccore\exception\NotFoundException;
use sigawa\mvccore\helpers\RouteGroupHelper;
use sigawa\mvccore\middlewares\BaseMiddleware;

class Router
{
    use RouteGroupHelper;

    private Request $request;
    private Response $response;
    private array $routeMap = [
        'get' => [],
        'post' => [],
        'delete' => [],
        'put' => [],
        'patch' => []
    ];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    private function addRoute(string $method, string $url, $callback, array $middlewares = [])
    {
        $fullUrl = $this->applyGroupPrefix($url);
        $allMiddlewares = $this->applyGroupMiddlewares($middlewares);
        $this->routeMap[strtolower($method)][trim($fullUrl, '/')] = [
            'callback' => $callback,
            'middlewares' => $allMiddlewares
        ];
    }


    public function get(string $url, $callback, array $middlewares = [])
    {
        $this->addRoute('get', $url, $callback, $middlewares);
    }

    public function post(string $url, $callback, array $middlewares = [])
    {
        $this->addRoute('post', $url, $callback, $middlewares);
    }

    public function delete(string $url, $callback, array $middlewares = [])
    {
        $this->addRoute('delete', $url, $callback, $middlewares);
    }

    public function put(string $url, $callback, array $middlewares = [])
    {
        $this->addRoute('put', $url, $callback, $middlewares);
    }

    public function patch(string $url, $callback, array $middlewares = [])
    {
        $this->addRoute('patch', $url, $callback, $middlewares);
    }

    private function getRouteMap(string $method): array
    {
        return $this->routeMap[strtolower($method)] ?? [];
    }

    private function parseRoute(string $route): string
    {
        return "@^" . preg_replace_callback(
            '/\{(\w+)(:([^}]+))?}/',
            fn($matches) => isset($matches[3]) ? "({$matches[3]})" : '(\w+)',
            trim($route, '/')
        ) . "$@";
    }

    private function matchRoute(string $url, string $method, array $routes): ?array
    {
        foreach ($routes as $route => $details) {
            $routeRegex = $this->parseRoute($route);

            if (preg_match($routeRegex, $url, $matches)) {
                array_shift($matches); // Remove full match

                if (preg_match_all('/\{(\w+)/', $route, $paramNames)) {
                    $routeParams = array_combine($paramNames[1], $matches);
                    $this->request->setRouteParams($routeParams);
                }

                return $details;
            }
        }

        return null;
    }

    public function resolve()
    {
        $method = $this->request->getMethod();
        $url = trim($this->request->getUrl(), '/');
        $routes = $this->getRouteMap($method);

        $matchedRoute = $this->matchRoute($url, $method, $routes);

        if (!$matchedRoute) {
            throw new NotFoundException("Route not found: $method $url");
        }

        $callback = $matchedRoute['callback'];
        if (!is_callable($callback) && !(is_array($callback) && class_exists($callback[0]) && method_exists($callback[0], $callback[1]))) {
            throw new \Exception("Invalid route callback defined for route: $method $url");
        }

        // Route-specific middleware
        foreach ($matchedRoute['middlewares'] ?? [] as $middleware) {
            if ($middleware instanceof BaseMiddleware) {
                $middleware->execute($this->request, $this->response);
            } else {
                throw new \Exception("Invalid middleware provided.");
            }
        }

        // Controller middleware
        if (is_array($callback)) {
            $controller = new $callback[0]();
            $controller->action = $callback[1];
            Application::$app->controller = $controller;

            foreach ($controller->getMiddlewares() as $middleware) {
                if ($middleware instanceof BaseMiddleware) {
                    $middleware->execute($this->request, $this->response);
                } else {
                    throw new \Exception("Invalid middleware provided.");
                }
            }

            $callback[0] = $controller;
        }

        return call_user_func($callback, $this->request, $this->response, ...$this->request->getRouteParams());
    }
}

<?php

namespace sigawa\mvccore;

use sigawa\mvccore\exception\NotFoundException;
use sigawa\mvccore\middlewares\BaseMiddleware;

class Router
{
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
        $this->routeMap[strtolower($method)][$url] = [
            'callback' => $callback,
            'middlewares' => $middlewares
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
                array_shift($matches); // Remove the full regex match

                if (preg_match_all('/\{(\w+)/', $route, $paramNames)) {
                    $routeParams = array_combine($paramNames[1], $matches);
                    $this->request->setRouteParams($routeParams);
                }

                return $details;
            }
        }

        return null;
    }

    public function group(string $prefix, callable $callback, array $middlewares = [])
    {
        $previousRoutes = $this->routeMap; // Backup existing routes

        $this->routeMap = array_map(function ($routes) use ($prefix, $middlewares) {
            $newRoutes = [];
            foreach ($routes as $route => $details) {
                $newRoute = rtrim($prefix, '/') . '/' . ltrim($route, '/');
                $newRoutes[$newRoute] = [
                    'callback' => $details['callback'],
                    'middlewares' => array_merge($middlewares, $details['middlewares'] ?? [])
                ];
            }
            return $newRoutes;
        }, $this->routeMap);

        // Execute the callback (which will register routes within this modified context)
        call_user_func($callback, $this);

        // Merge back the updated routes
        foreach ($this->routeMap as $method => $routes) {
            $previousRoutes[$method] = array_merge($previousRoutes[$method], $routes);
        }
        $this->routeMap = $previousRoutes; // Restore full routes
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
        $middlewares = $matchedRoute['middlewares'] ?? [];
        // Execute route-specific middlewares
        foreach ($middlewares as $middleware) {
            if ($middleware instanceof BaseMiddleware) {
                $middleware->execute($this->request, $this->response);
            } else {
                throw new \Exception("Invalid middleware provided.");
            }
        }

        // Handle controller callbacks
        if (is_array($callback)) {
            $controller = new $callback[0]();
            $controller->action = $callback[1];
            Application::$app->controller = $controller;

            // Execute controller-specific middlewares
            foreach ($controller->getMiddlewares() as $middleware) {
                if ($middleware instanceof BaseMiddleware) {
                    $middleware->execute($this->request, $this->response);
                } else {
                    throw new \Exception("Invalid middleware provided.");
                }
            }

            $callback[0] = $controller;
        }

        // Execute the callback with route parameters
        return call_user_func($callback, $this->request, $this->response, ...$this->request->getRouteParams());
    }
}

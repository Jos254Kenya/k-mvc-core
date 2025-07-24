<?php

namespace sigawa\mvccore\helpers;

trait RouteGroupHelper
{
    protected array $groupPrefixStack = [];
    protected array $groupMiddlewareStack = [];

    protected function applyGroupPrefix(string $route): string
    {
        $prefix = implode('', $this->groupPrefixStack);
        return rtrim($prefix, '/') . '/' . ltrim($route, '/');
    }

    protected function applyGroupMiddlewares(array $middlewares): array
    {
        return array_merge(...array_merge($this->groupMiddlewareStack, [$middlewares]));
    }


    public function group(string $prefix, callable $callback, array $middlewares = [])
    {
        $this->groupPrefixStack[] = '/' . trim($prefix, '/');
        $this->groupMiddlewareStack[] = $middlewares;

        $callback($this); // Register routes within this context

        array_pop($this->groupPrefixStack);
        array_pop($this->groupMiddlewareStack);
    }
}

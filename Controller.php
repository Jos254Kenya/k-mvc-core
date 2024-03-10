<?php

namespace Merudairy\Fmmerudairy\core;
use Merudairy\Fmmerudairy\core\middlewares\BaseMiddleware;
class Controller
{
    public string $layout = 'main';
    public string $action = '';

    /**
     * @var BaseMiddleware[]
     */
    protected array $middlewares = [];

    public function setLayout($layout): void
    {
        $this->layout = $layout;
    }

    public function render($view, $params = [], $layoutDirectory = ''): string
    {
        return Application::$app->view->renderView($view, $params, $layoutDirectory);
    }


    public function registerMiddleware(BaseMiddleware $middleware)
    {
        $this->middlewares[] = $middleware;
    }
    /**
     * @return BaseMiddleware[]
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
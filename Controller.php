<?php

namespace sigawa\mvccore;

use sigawa\mvccore\middlewares\BaseMiddleware;

class Controller
{
    public string $layout = 'main';
    public string $action = '';

    /**
     * @var BaseMiddleware[]
     */
    protected array $middlewares = [];

    /**
     * Pre-action hooks.
     * @var callable[]
     */
    protected array $beforeActions = [];

    /**
     * Post-action hooks.
     * @var callable[]
     */
    protected array $afterActions = [];

    /**
     * Set the layout for the controller.
     * @param string $layout
     */
    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Render a view with optional parameters and a custom layout directory.
     * @param string $view
     * @param array $params
     * @param string $layoutDirectory
     * @return string
     */
    public function render(string $view, array $params = [], string $layoutDirectory = ''): string
    {
        return Application::$app->view->renderView($view, $params, $layoutDirectory);
    }

    /**
     * Register a middleware for the controller.
     * @param BaseMiddleware $middleware
     */
    public function registerMiddleware(BaseMiddleware $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Get all registered middlewares.
     * @return BaseMiddleware[]
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Add a pre-action hook to be executed before an action.
     * @param callable $callback
     */
    public function beforeAction(callable $callback): void
    {
        $this->beforeActions[] = $callback;
    }

    /**
     * Add a post-action hook to be executed after an action.
     * @param callable $callback
     */
    public function afterAction(callable $callback): void
    {
        $this->afterActions[] = $callback;
    }

    /**
     * Execute all pre-action hooks.
     */
    protected function executeBeforeActions(): void
    {
        foreach ($this->beforeActions as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * Execute all post-action hooks.
     */
    protected function executeAfterActions(): void
    {
        foreach ($this->afterActions as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * Run the specified action with pre and post hooks.
     * @param string $action
     * @param array $params
     * @return mixed
     */
    public function runAction(string $action, array $params = [])
    {
        $this->action = $action;
        $this->executeBeforeActions();

        try {
            $result = call_user_func_array([$this, $action], $params);
        } catch (\Exception $e) {
            $this->handleActionError($e);
            return null;
        }

        $this->executeAfterActions();
        return $result;
    }

    /**
     * Handle errors that occur during action execution.
     * @param \Exception $e
     */
    protected function handleActionError(\Exception $e): void
    {
        Application::$app->response->internalServerError([
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * Send a JSON response from the controller.
     * @param array $data
     * @param int $statusCode
     */
    public function jsonResponse(array $data, int $statusCode = 200): void
    {
        Application::$app->response->json($data, $statusCode);
    }
}

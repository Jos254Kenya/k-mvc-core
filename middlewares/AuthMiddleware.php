<?php

namespace sigawa\mvccore\middlewares;

use sigawa\mvccore\Application;
use sigawa\mvccore\Request;
use sigawa\mvccore\Response;
use sigawa\mvccore\exception\ForbiddenException;

class AuthMiddleware extends BaseMiddleware
{
    protected array $actions = [];
    protected ?string $redirectUrl = null;

    /**
     * AuthMiddleware constructor.
     *
     * @param array $actions List of actions where this middleware applies.
     * @param string|null $redirectUrl URL to redirect unauthenticated users (optional).
     */
    public function __construct(array $actions = [], ?string $redirectUrl = null)
    {
        $this->actions = $actions;
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * Execute the middleware.
     *
     * @param Request $request
     * @param Response $response
     * @throws ForbiddenException
     */
    public function execute(Request $request, Response $response)
    {
        // Check if the user is a guest
        if (Application::isGuest()) {
            // If no actions are specified or the current action matches, apply the middleware logic
            if (empty($this->actions) || in_array(Application::$app->controller->action, $this->actions)) {
                if ($this->redirectUrl) {
                    // Redirect unauthenticated users
                    $response->redirect($this->redirectUrl);
                    return;
                }
                // Throw a forbidden exception if no redirect is specified
                throw new ForbiddenException("You must be logged in to access this resource.");
            }
        }
    }
}

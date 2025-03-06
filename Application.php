<?php

namespace sigawa\mvccore;

use sigawa\mvccore\db\Database;
use Dotenv\Dotenv;
use sigawa\mvccore\services\RedisService; // For environment configuration

class Application
{
    const EVENT_BEFORE_REQUEST = 'beforeRequest';
    const EVENT_AFTER_REQUEST = 'afterRequest';

    protected array $eventListeners = [];
    public static Application $app;
    public static string $ROOT_DIR;
    public string $userClass;
    public string $layout = 'main';
    public Router $router;
    public Request $request;
    public Response $response;
    public ?Controller $controller = null;
    public Database $db;
    public Session $session;
    public View $view;
    public ?UserModel $user;
    public AuthProvider $auth;

    public array $services = []; // Service container
    public RedisService $redis;
    public function __construct($rootDir, $config)
    {
        $this->user = null;
        $this->userClass = $config['userClass'];
        self::$ROOT_DIR = $rootDir;
        self::$app = $this;

        $this->loadEnvironment($rootDir);

        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        $this->db = new Database($config['db']);
        $this->session = new Session();
        $this->view = new View();
        $this->redis = new RedisService();

        // Register default services
        $this->registerService('request', $this->request);
        $this->registerService('response', $this->response);
        $this->registerService('router', $this->router);
        $this->registerService('db', $this->db);
        $this->registerService('session', $this->session);
        $this->registerService('view', $this->view);
        $this->registerService('redis', $this->redis);
        $this->auth = new AuthProvider();
        $this->registerService('auth', $this->auth);


        if ($userId = $this->session->get('user')) {
            $user = $this->userClass::findOne([$this->userClass::primaryKey() => $userId]);
            if ($user) {
                AuthProvider::setUser($user);
            } else {
                // Clear user session
                $this->session->remove('user');
                // Redirect to staff login page
                $this->response->redirect($_ENV['MAIN_REDIRECT_PAGE']); // Adjust the URL as needed
                exit; // Ensure script execution stops after redirect
            }
        }
    }

    private function loadEnvironment($rootDir)
    {
        if (file_exists($rootDir . '/.env')) {
            $dotenv = Dotenv::createImmutable($rootDir);
            $dotenv->load();
        }
    }

    public static function isGuest()
    {
        return !self::$app->user;
    }

    public function login(UserModel $user)
    {
        AuthProvider::setUser($user);
        return true;
    }

    public function guestLogin(UserModel $user)
    {
        $this->user = $user;
        $className = get_class($user);
        $primaryKey = $className::primaryKey();
        $value = $user->{$primaryKey};
        Application::$app->session->set('guest', $value);
        return true;
    }

    public function logout()
    {
        AuthProvider::logout();
    }

    public function logoutGuest()
    {
        $this->user = null;
        self::$app->session->remove('guest');
    }

    public function run()
    {
        $this->triggerEvent(self::EVENT_BEFORE_REQUEST);
        try {
            echo $this->router->resolve();
        } catch (\Exception $e) {
            $this->handleException($e);
        } finally {
            $this->triggerEvent(self::EVENT_AFTER_REQUEST);
        }
    }

    private function handleException(\Exception $e)
    {
        $request = $this->request;
        $response = $this->response;

        // Check if the request is an API request
        $isApiRequest = str_starts_with($request->getUrl(), '/api');
        // Determine HTTP status code
        $statusCode = method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500;
        $response->statusCode($statusCode);

        if ($isApiRequest) {
            // Return JSON error response for API requests
            return $response->json([
                'error' => $e->getMessage(),
                'status' => $statusCode
            ], $statusCode);
        }

        // For web requests, render an error page
        if (getenv('APP_DEBUG') === 'true') {
            echo $this->view->renderView('_error', ['exception' => $e]);
        } else {
            $response->statusCode(500);
            echo $this->view->renderView('_error', ['exception' => $e]);
        }
    }


    public function triggerEvent($eventName)
    {
        $callbacks = $this->eventListeners[$eventName] ?? [];
        foreach ($callbacks as $callback) {
            call_user_func($callback);
        }
    }

    public function on($eventName, $callback)
    {
        $this->eventListeners[$eventName][] = $callback;
    }

    public function registerService(string $name, $service)
    {
        $this->services[$name] = $service;
    }

    public function getService(string $name)
    {
        return $this->services[$name] ?? null;
    }
}

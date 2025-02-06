<?php

namespace sigawa\mvccore;

use sigawa\mvccore\db\Database;
use Dotenv\Dotenv; // For environment configuration

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
    public array $services = []; // Service container

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

        // Register default services
        $this->registerService('request', $this->request);
        $this->registerService('response', $this->response);
        $this->registerService('router', $this->router);
        $this->registerService('db', $this->db);
        $this->registerService('session', $this->session);
        $this->registerService('view', $this->view);

        $userId = Application::$app->session->get('user');
        if ($userId) {
            $key = $this->userClass::primaryKey();
            $this->user = $this->userClass::findOne([$key => $userId]);
            if (!$this->user) {
                // Clear user session
                Application::$app->session->remove('user');
                // Redirect to staff login page
                $this->response->redirect("stafflogin"); // Adjust the URL as needed
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
        $this->user = $user;
        $className = get_class($user);
        $primaryKey = $className::primaryKey();
        $value = $user->{$primaryKey};
        Application::$app->session->set('user', $value);
        return true;
    }

    public function loginCustomer(UserModel $user)
    {
        $this->user = $user;
        $className = get_class($user);
        $primaryKey = $className::primaryKey();
        $value = $user->{$primaryKey};
        Application::$app->session->set('customerid', $value);
        return true;
    }

    public function logout()
    {
        $this->user = null;
        self::$app->session->remove('user');
    }

    public function logoutCustomer()
    {
        $this->user = null;
        self::$app->session->remove('customerid');
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
        // Centralized error handling
        if (getenv('APP_DEBUG') === 'true') {
            echo $this->view->renderView('_error', [
                'exception' => $e,
            ]);
        } else {
            $this->response->statusCode(500);
            echo $this->view->renderView('_error', [
                'exception' => $e,
            ]);
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

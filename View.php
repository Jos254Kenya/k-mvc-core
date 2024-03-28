<?php

namespace sigawa\mvccore;

use Exception;
use sigawa\mvccore\exception\NotFoundException;

class View
{
    public string $title = 'Karibu';

    public function renderView($view, array $params, $layoutDirectory = '')
    {
        try {
            $layoutName = Application::$app->layout;

            if (!empty($layoutDirectory)) {
                $layoutName = $layoutDirectory . '/' . $layoutName;
            }

            if (Application::$app->controller) {
                $controllerLayout = Application::$app->controller->layout;
                if (!empty($layoutDirectory)) {
                    $controllerLayout = $layoutDirectory . '/' . $controllerLayout;
                }
                $layoutName = $controllerLayout;
            }

            $viewContent = $this->renderViewOnly($view, $params);
            ob_start();
            include_once Application::$ROOT_DIR."/views/layouts/$layoutName.php";
            $layoutContent = ob_get_clean();
            return str_replace('{{content}}', $viewContent, $layoutContent);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function renderViewOnly($view, array $params)
    {
        try {
            foreach ($params as $key => $value) {
                $$key = $value;
            }
            $viewFile = Application::$ROOT_DIR . "/views/$view.php";
            if (!file_exists($viewFile)) {
                throw new Exception("View file '$view.php' not found.");
            }
            ob_start();
            include_once $viewFile;
            return ob_get_clean();
        } catch (Exception $e) {
            // You can customize the error handling here
            // For example, throw a custom NotFoundException
            throw new Exception($e->getMessage());
        }
    }
}

<?php 

namespace sigawa\mvccore\view;

use Exception;

class PhpRenderer implements ViewRendererInterface
{
    public function render(string $viewPath, array $params): string
    {
        if (!file_exists($viewPath)) {
            throw new Exception("PHP view file not found: $viewPath");
        }

        extract($params);
        ob_start();
        include $viewPath;
        return ob_get_clean();
    }
}

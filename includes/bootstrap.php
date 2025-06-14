<?php
// includes/bootstrap.php

namespace McConsole;

// Define base path for global access
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// PSR-4 Autoloader for McConsole internal namespaces
spl_autoload_register(function ($class) {
    $prefix = 'McConsole\\';
    $base_dir = BASE_PATH . '/includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

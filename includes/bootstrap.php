<?php
// includes/bootstrap.php

namespace McConsole;

// Set BASE_PATH to the current working directory (user's project root)
if (!defined('BASE_PATH')) {
    define('BASE_PATH', getcwd());
}

// Register autoloader for McConsole internal classes
spl_autoload_register(function ($class) {
    $prefix = 'McConsole\\';
    $base_dir = __DIR__ . '/';  // Use __DIR__ for internal McConsole code

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

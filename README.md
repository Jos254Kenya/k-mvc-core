
# Custom MVC Framework

![PHP](https://img.shields.io/badge/php-7.4-blue)
![Composer](https://img.shields.io/badge/composer-2.0-orange)
![License](https://img.shields.io/badge/license-MIT-green)

## 🚀 Introduction

Welcome to the **Custom MVC Framework**! This framework is inspired by the routing capabilities of Laravel, Symfony, and CodeIgniter. It's designed to be **lightweight, flexible, and easy to use**, making it ideal for developers seeking to understand the inner workings of an MVC framework or build small to medium-sized applications without the overhead of a full-fledged framework.

## 🛠️ Features

- **Lightweight and Fast**: Minimal overhead, optimized for performance.
- **MVC Structure**: Clean separation of concerns with Models, Views, and Controllers.
- **Powerful Routing**: Simplified and efficient routing system.
- **Database Integration**: User-friendly database abstraction layer.
- **CRUD Class**: Pre-built, customizable CRUD operations.
- **Command-Line Utility**: The `mcconsole` tool for seamless project management (create, make, serve, etc.).
- **Templating Engine**: Basic templating for dynamic views.
- **Error Handling**: Developer-friendly error pages and detailed stack traces.

## 📋 Requirements

- PHP 7.4 or higher
- Composer

---

## 🧑‍💻 Installation

### 1. Install via Composer

```bash
composer require sigawa/mvc-core:^1.0.2
```

### 2. Clone the Repository

```bash
git clone https://github.com/Jos254Kenya/k-mvc-core.git
```

After cloning, copy the `mcconsole` file to your project root directory to enable the command-line utility.

### 3. Initialize a New Project

```bash
php mcconsole create:project
```

Follow the prompts to set up your project structure automatically.

### 4. Configure Your Environment

Copy the example `.env` file and customize it for your environment:

```bash
cp .env.example .env
```

---

## 🚦 Getting Started

### Running the Application

Use the built-in PHP server or the `mcconsole serve` command:

```bash
php -S localhost:8000 -t public
```

or

```bash
php mcconsole serve
```

Visit `http://localhost:8000` to view your application.

---

## 📚 Documentation

### Routing

Define routes in your `public/index.php` file:

```php
$app->router->get('/', [HomeController::class, 'index']);
$app->router->post('/submit', [HomeController::class, 'functionName']);
```

### Controllers

Generate a controller using:

```bash
php mcconsole make:controller ControllerName
```

Example:

```php
namespace App\Controllers;

use sigawa\mvccore\Request;
use sigawa\mvccore\Response;
use sigawa\mvccore\Controller;

class HomeController extends Controller
{
    public function index(Request $request, Response $response)
    {
        $this->setLayout('main');
        return $this->render('home');
    }
}
```

### Models

Generate a model using:

```bash
php mcconsole make:model ModelName
```

Example:

```php
namespace App\Models;

use sigawa\mvccore\db\DbModel;

class User extends DbModel
{
    public string $name = '';
    public string $email = '';

    public static function tableName(): string
    {
        return 'users';
    }

    public function attributes(): array
    {
        return ['name', 'email'];
    }

    public function rules(): array
    {
        return [
            'name' => [self::RULE_REQUIRED],
            'email' => [self::RULE_REQUIRED, self::RULE_EMAIL],
        ];
    }
}
```

### CRUD Operations

Example CRUD usage:

```php
use sigawa\mvccore\db\CRUD;

$crud = new CRUD($databaseConnection);
$data = $crud->getAll('tableName', '*', []);
```
# NOTE: More of these CRUD methods are implemented in the Model class and can be called statically in the model classes
Example:
```php
<?php

namespace MyNamspace\Vendor\models;

use sigawa\mvccore\db\DbModel;

class Itinerary extends DbModel
{
    public $id;
    public $client_id;
    public $agent_id;
    public $start_date;
    public $end_date;
    public $total_cost;
    public $profit_margin;
    public $created_at;
    public static function tableName(): string
    {
        return 'itineraries';
    }
    public function attributes(): array
    {
        return [];
    }
    public function labels(): array
    {
        return [];
    }
    public static function getItineraryDetails(int $id)
    {
        $query = "SELECT i.*, c.name AS client_name, a.name AS agent_name
                  FROM itineraries i
                  LEFT JOIN clients c ON i.client_id = c.id
                  LEFT JOIN agents a ON i.agent_id = a.id
                  WHERE i.id = :id";
        return self::findOneByQuery($query, ['id' => $id]);
    }
    public static function getAllItineraries(): array
    {
        $query = "SELECT i.*, c.name AS client_name, a.name AS agent_name
                  FROM itineraries i
                  LEFT JOIN clients c ON i.client_id = c.id
                  LEFT JOIN agents a ON i.agent_id = a.id";
        return self::findByQuery($query);
    }
    public function rules(): array
    {
        return [
            'client_id' => [self::RULE_REQUIRED],
            'start_date' => [self::RULE_REQUIRED],
            'end_date' => [self::RULE_REQUIRED],
            'total_cost' => [self::RULE_REQUIRED, self::RULE_DIGIT],
            'profit_margin' => [self::RULE_DIGIT],
        ];
    }
    public function save(): bool
    {
        $this->created_at = date('Y-m-d H:i:s');
        return parent::save();
    }

}


```
### Views

Create views in the `app/views` directory:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
</head>
<body>
    <h1>Welcome to the Custom MVC Framework!</h1>
</body>
</html>
```

---

## 🤝 Contributing

Contributions are welcome! Submit a pull request or open an issue to help improve this framework.

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).

---

## ❤️ Acknowledgments

- Inspired by Laravel, Symfony, and CodeIgniter routing mechanisms.
- Special thanks to all contributors.

---

**Happy Coding!** 🎉
- No hard feelings. I created this project as a beginner in MVC, feel free to critisize and do not forget to point out the burning issues you discovered.
- I depend on your feedback, positive or discouraging, am ready to gusp all...so, start sending them right away.
- Explore the architectutre ... create from or add to IT
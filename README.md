
# Custom MVC Framework

![PHP](https://img.shields.io/badge/php-7.4-blue)
![Composer](https://img.shields.io/badge/composer-2.0-orange)
![License](https://img.shields.io/badge/license-MIT-green)

## Introduction

Welcome to my custom MVC framework! This framework is inspired by in-depth knowledge of Laravel, symfony and Codeignitor Routing capabilities and is designed to be lightweight, flexible, and easy to use. It is perfect for developers who want to understand the inner workings of an MVC framework or those who want to build small to medium-sized applications without the overhead of a full-fledged framework.

## Installation

You can install this framework via Composer:

```bash
composer require sigawa/mvc-core:^1.0.1
```

## Features

- **Lightweight and Fast**: Minimal overhead and optimized for performance.
- **MVC Structure**: Clean separation of concerns with Models, Views, and Controllers.
- **Routing**: Simple and powerful routing system.
- **Database Integration**: Easy-to-use database abstraction layer.
- **CRUD class ready**: All CRUD operations in handy and easily customizable.
- **Command line tool 'mcconsole'**: Run common command line commands (create, make ..) hassle free. by using the 'mcconsole' command utility class
- **Templating**: Basic templating engine for dynamic views.
- **Error Handling**: Friendly error pages and detailed stack traces.

## Requirements

- PHP 7.4 or higher
- Composer

## Getting Started

### 1. Installation

Ensure that you have initialized the environment before cloning the repository. i.e., run 
```bash
composer init
```
first, then

Clone the repository or install via Composer:

```bash
composer require sigawa/mvc-core:^1.0.1
//OR
git clone https://github.com/Jos254Kenya/k-mvc-core.git
```
Copy the mcconsole file to your project root folder to access it in your terminal.

You can initialize a new project using:

```bash
php mcconsole create:project
# next enter your project name
# it will automatically create the project structure with initial file
```

### 2. Configuration

After installation, configure your application by copying the example environment file and updating it with your configuration:

```bash
cp .env.example .env
```
copy the mcconsole file to your project root directory, if u wish to use it for CLI

### 2.1. User class
Run the following command to create a user model:

```bash
php mcconsole make:model User
```
Replace the function/class body with the one in the User.example file. Ensure your class includes:

```bash
use sigawa\mvccore\UserModel;
```

### 2.2. index.php
Copy the contents of index.example to `/app/public/index.php`.

```bash
php mcconsole make:model User
```
then you will need to add the replace the function/class body with the one in the 'User.example' file
the file provides a basic structure of a typical model class. You may modify it as per your need BUT remember
to always have the following  line in your class
```bash
use sigawa\mvccore\UserModel;
```
### 3. Running the Application

Use the built-in PHP server or the mcconsole serve command to run your application:

```bash
php -S localhost:8000 -t public
```
OR
```bash
php mcconsole serve
```

Navigate to `http://localhost:8000` in your browser to see the application in action.

## Documentation

### Routing

Define your routes in the `public/index.php` file:

```php
$app->router->get('/', [HomeController::class,'index']);
$router->post('/submit', [HomeController::class,'functioname']);
//$router->get('/get/${id}/',[HomeController::class,'functioname']);
```


### Controllers

Create controllers in the `app/Controllers` directory using:
```bash
php mcconsole make:controller Controllername
```
Example controller:

```php
namespace App\Controllers;

use sigawa\mvccore\Request;
use sigawa\mvccore\Response;
use sigawa\mvccore\Controller;

class ControllernameController extends Controller
{
    public function index(Request $request, Response $response)
    {
        $this->setLayout('layoutname');
        // return $this->render($view, $params = [] optional, $layoutDirectory = '' optional);
        // by default, your layouts will be in the App/views/layout
        return $this->render('home');
    }
}
```

### Models

Create models in the `app/Models` directory using:
```bash
php mcconsole make:model Modelname
```
Example model:

```php
namespace App\Models;

use sigawa\mvccore\db\DbModel;

class permission extends DbModel
{
    public string $PermissionName ='';
    public string $Description  ='';

    public static function tableName(): string
    {
        return 'permission';
    }
    public function attributes(): array
    {
        return ['PermissionName','Description'];
    }

    public function rules()
    {
        return [
            'attributename'=>[self::RULE_REQUIRED],
            // other attributes and rules, explore the multiple rules in the Base method
           
        ];
    }
    public function save()
    {
        return parent::save();
    }
}
```
### CRUD

Access all `CRUD` functions in the CRUD class.
Example:

```php
namespace namespace\Controllers;

use Sigawa\Hcp\models\permission;
use sigawa\mvccore\Application;
use sigawa\mvccore\db\CRUD;
use sigawa\mvccore\Request;
use sigawa\mvccore\Controller;

class PermissionsController extends Controller
{
    private $crud;
    private $permision;
    public function __construct(){
        $this->crud =new CRUD(Application::$app->db);
        $this->permision =new permission();
    }
    public function index()
    {
        // Logic for your index method goes here
        $this->setLayout('authenticated');
        return $this->render('permissions');
    }
    public function loadpermission(Request $request)
    {
        if($request->getMethod()==='get'){
            $data =$this->crud->getAll('permission','*',[]);
            echo json_encode($data);
        }
    }
    public function update(Request $request)
    {
        if($request->getMethod()==='post')
        {
            $input = json_decode(file_get_contents('php://input'), true);
            $description = $input['Description'];
            $id =$input['id'];
            $data =['Description' =>$description];
            $condition= ['id'=>$id];
            $updateResult =$this->crud->update('permission',$data,$condition);
            if ($updateResult['success']) {
                if ($updateResult['changesMade']) {
                    return true;
                } else {
                    echo json_encode("You did not make any changes");
                }
            } else
                {
                echo json_encode('Update Failed. Kindly make sure you typed the right data');
            }
        }
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
    <h1>Welcome to My Custom MVC Framework!</h1>
</body>
</html>
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request or open an Issue to help improve this project.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Acknowledgments

- Inspired by Laravel, symfony and codeignitor routing mechanism
- Special thanks to all contributors

---

Happy coding!

## SIGAWA


# Custom MVC Framework

![PHP](https://img.shields.io/badge/php-7.4-blue)
![Composer](https://img.shields.io/badge/composer-2.0-orange)
![License](https://img.shields.io/badge/license-MIT-green)

## 🚀 Introduction

Welcome to the **Custom MVC Framework**! This framework is inspired by the routing capabilities of Laravel, Symfony, and CodeIgniter. It's designed to be **lightweight, flexible, and easy to use**, making it ideal for developers seeking to understand the inner workings of an MVC framework or build small to medium-sized applications without the overhead of a full-fledged framework.
`
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
composer require sigawa/mvc-core:^1.0.6
```
### WHAT'S NEW 
API calls and protected routes using Middlewares
Auth logic handling seamlessly with access_tokens
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
$app->router->get('/', [NameController::class, 'index']);
$app->router->get('view/{id}', [NameController::class, 'view']);
$app->router->post('/submit', [NameController::class, 'functionName']);
$app->router->put('/submit/{id}', [NameController::class, 'functionName']);
$app->router->delete('/submit/{id}', [NameController::class, 'functionName']);
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
### Bonus
In the vendor directory, locate the js/base.js file and you may use it in your frontend js files for 
sanitizing and forming the formData object
```javascript
    function formToJSON(form) {
  const formData = new FormData(form);
  const json = {};

  formData.forEach((value, key) => {
    if (key.includes("[")) {
      const keys = key.split(/\[|\]/).filter(Boolean);
      let current = json;

      keys.forEach((nestedKey, index) => {
        if (!current[nestedKey]) {
          current[nestedKey] = isNaN(keys[index + 1]) ? {} : [];
        }
        if (index === keys.length - 1) {
          // Only assign if value is not null or empty
          if (value !== null && value !== "") {
            current[nestedKey] = value;
          }
        }
        current = current[nestedKey];
      });
    } else {
      // Simple fields
      if (value !== null && value !== "") {
        json[key] = value;
      }
    }
  });

  // Remove null/undefined values from arrays (e.g., dynamically created arrays/inputs)
  const cleanArrays = (obj) => {
    Object.keys(obj).forEach((key) => {
      if (Array.isArray(obj[key])) {
        obj[key] = obj[key].filter((item) => item !== null && item !== undefined && Object.keys(item).length > 0);
      } else if (typeof obj[key] === "object") {
        cleanArrays(obj[key]);
      }
    });
  };

  cleanArrays(json);
  return json;
}
// usage example:
   $("#kt_modal_add_agent_form").on("submit", function (e) {
      e.preventDefault();
      let $submitButton = $("#agent_save_btn");
      $submitButton.prop("disabled", true).text("Please wait...");
  
      const formData = formToJSON($(this)[0]);
      const agentId = $(this).data("agentId");  // Get agent ID (if editing)
  
      const url = agentId ? `/agents/update/${agentId}` : "/agents/create"; // Use agentId for update
      const method = agentId ? "PUT" : "POST"; // Use PUT for update
  
      $.ajax({
        url: url,
        method: method,
        contentType: "application/json",
        data: JSON.stringify(formData),
        success: function (response) {
          $submitButton.prop("disabled", false).text("Submit");
          if (response.success) {
            Swal.fire({
              icon: "success",
              title: "Success",
              text: response.message,
            });
            // Update the grid data silently
            if (response.data) {
              gridDatatable.updateGridData(response.data);
            }
            // Close the modal
            $("#kt_modal_add_agent").modal("hide");
            // Swal.fire("Error", Array.isArray(response.errors) ? response.errors.join(", ") : response.errors || "An error occurred.");
            // Reset form
            $("#kt_modal_add_agent_form")[0].reset();
            $("#kt_modal_add_agent_form").removeData("agentId"); // Clear the agent ID
            $(".modal-header h4").text("Add an Agent");
          } else {
            Swal.fire({
              icon: "error",
              title: "Error",
              text: Array.isArray(response.errors) ? response.errors.join(", ") : response.errors || "An error occurred."
            });
          }
        },
        error: function (xhr) {
          $submitButton.prop("disabled", false).text("Submit");
          Swal.fire({
            icon: "error",
            title: "Error",
            text: xhr.responseJSON?.message || "An unexpected error occurred.",
          });
        },
      });
    });
  
    // Clear modal state on close
    $("#kt_modal_add_agent").on("hidden.bs.modal", function () {
      $("#kt_modal_add_agent_form")[0].reset();
      $("#kt_modal_add_agent_form").removeData("agentId"); // Remove agent ID
      $(".modal-header h4").text("Add an Agent");
    });

```
### OTHER ESSENTIAL CLASSES:
For file serving, you can make use of the UtilityController.example class
For pdf generation, you  can make use of the the PDFGenerator.example class

## SINCERITY
I recognize that I may be having ambitious ideas to revolutionize our my MVC ubderstanding, and that’s a great direction. However, I admit that I have so many conspicous flaws (may be irritating), that is why knowledge is progressive and I will always improve and learn more especially from your observations.

### A full controller example, say, ClientsController
NOTE: The Trekafrica namespace is used for demonstration purposes only and should not be used in your application whatsoever.
```php
<?php

namespace Sigawa\Trekafrica\controllers;

use sigawa\mvccore\Request;
use sigawa\mvccore\Response;
use sigawa\mvccore\Controller;
use Sigawa\Trekafrica\models\Clients;

class ClientsController extends Controller
{
    public function index(Request $request, Response $response)
    {
        if($request->isGet()){
            $this->setLayout('authenticated');
            return $this->render('clients', [
                'datalist' => Clients::allClients(),
            ]);
        }
        else{
            return $response->redirect('/');
        }
       
    }
    public function create(Request $request, Response $response)
    {
        if ($request->isPost()) {
            $file = $_FILES['file_path'] ?? null;
            $allowedFileTypes = [
                'application/pdf',
                'image/jpeg',
                'image/jpg',
                'image/png'
            ];
            $uploadedFilePath = null;

            if ($file && isset($file['name']) && !empty($file['name'])) {
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $uploadErrors = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds the upload_max_filesize directive.',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds the MAX_FILE_SIZE directive.',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
                    ];
                    return $response->json([
                        'success' => false,
                        'message' => $uploadErrors[$file['error']] ?? 'Unknown upload error.'
                    ]);
                }
                // Validate file type using finfo_file
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $fileType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($fileType, $allowedFileTypes)) {
                    return $response->json([
                        'success' => false,
                        'message' => 'Invalid file type. Only images or PDFs are allowed.'
                    ]);
                }
                // Define upload directory securely
                $uploadDir = __DIR__ . '/../uploads/files/'; //this can be improved to take care of directory traversal 
                // unrelated example :  $file = basename($request->getBody()['file']); // Prevent basic directory traversal
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                    return $response->json([
                        'success' => false,
                        'message' => 'Failed to create upload directory.'
                    ]);
                }
                // Generate a unique filename
                $uniqueFilename = time() . '-' . md5(uniqid()) . '-' . basename($file['name']);
                $uploadedFilePath = $uploadDir . '/' . $uniqueFilename;
                if (!move_uploaded_file($file['tmp_name'], $uploadedFilePath)) {
                    return $response->json([
                        'success' => false,
                        'message' => 'Failed to upload file. Ensure file permissions are set correctly.'
                    ]);
                }
            }
            // Save client details to database
            $client = new Clients();
            $client->loadData($request->getBody());
            $client->file_path = $uploadedFilePath ? basename($uploadedFilePath) : null;

            try {
                if ($client->validate() && $client->save()) {
                    return $response->json([
                        'success' => true,
                        'message' => 'Client created successfully!',
                        'data' => Clients::allClients(),
                    ]);
                } else {
                    return $response->json([
                        'errors' => $client->getErrorMessages(),
                    ]);
                }
            } catch (\Exception $th) {
                return $response->json([
                    'message' => 'An error occurred while saving the client.',
                    'errors' => $th->getMessage(),
                ]);
            }
        }

        return $response->json([
            'success' => false,
            'error' => 'Invalid request method',
        ]);
    }
    public function update(Request $request, Response $response, $id)
    {
        if ($request->isPost()) {
            $id ??= $request->getParam('id');
            if (!$id) {
                return $response->json([
                    'success' => false,
                    'message' => 'Client ID is required.'
                ]);
            }
            $client = Clients::findOne(['id' => $id]);
            if (!$client) {
                return $response->json([
                    'success' => false,
                    'message' => 'Client not found.'
                ]);
            }

            $file = $_FILES['file_path'] ?? null;
            $allowedFileTypes = [
                'application/pdf',
                'image/jpeg',
                'image/jpg',
                'image/png'
            ];
            $uploadedFilePath = null;

            if ($file && isset($file['name']) && !empty($file['name'])) {
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $uploadErrors = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds the upload_max_filesize directive.',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds the MAX_FILE_SIZE directive.',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
                    ];
                    return $response->json([
                        'success' => false,
                        'message' => $uploadErrors[$file['error']] ?? 'Unknown upload error.'
                    ]);
                }

                // Validate file type
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $fileType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($fileType, $allowedFileTypes)) {
                    return $response->json([
                        'success' => false,
                        'message' => 'Invalid file type. Only images or PDFs are allowed.'
                    ]);
                }

                // Define upload directory securely from .env
                $uploadDir = __DIR__ . '/../uploads/files/';
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                    return $response->json([
                        'success' => false,
                        'message' => 'Failed to create upload directory.'
                    ]);
                }
                // Generate a unique filename
                $uniqueFilename = time() . '-' . md5(uniqid()) . '-' . basename($file['name']);
                $uploadedFilePath = $uploadDir . '/' . $uniqueFilename;

                if (!move_uploaded_file($file['tmp_name'], $uploadedFilePath)) {
                    return $response->json([
                        'success' => false,
                        'message' => 'Failed to upload file. Ensure file permissions are set correctly.'
                    ]);
                }

                // Delete old file if a new file is uploaded
                if ($client->file_path) {
                    $oldFilePath = $uploadDir . '/' . $client->file_path;
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                // Save new file path
                $client->file_path = basename($uploadedFilePath);
            }
            // Update other client details
            $client->loadData($request->getBody());
            try {
                if ($client->validate($id) && $client->save()) {
                    return $response->json([
                        'success' => true,
                        'message' => 'Client updated successfully!',
                        'data' => Clients::allClients(),
                        
                    ]);
                } else {
                    return $response->json([
                        'errors' => $client->getErrorMessages(),
                    ]);
                }
            } catch (\Exception $th) {
                return $response->json([
                    'message' => 'An error occurred while updating the client.',
                    'errors' => $th->getMessage(),
                ]);
            }
        }

        return $response->json([
            'success' => false,
            'error' => 'Invalid request method',
        ]);
    }
    public function view(Request $request, Response $response, $id)
    {
        $id ??= $request->getParam('id');
        $client = Clients::getClientById($id);
        if (!$client) {
            $response->statusCode(404);
            return $response->json([
                'success' => false,
                'message' => 'client not found!',
            ]);
        }

        return $response->json([
            'success' => true,
            'data' => $client,
        ]);
    }
    public function delete(Request $request, Response $response, $id)
    {
        if (!$request->isDelete()) {
            return $response->json([
                'success' => false,
                'message' => 'Invalid request method!',
            ]);
        }
        $id ??= $request->getParam('id');
        $client = Clients::getClientById($id);
        if (empty($client)) {
            $response->statusCode(404);
            return $response->json([
                'success' => false,
                'message' => 'client not found!',
            ]);
        }
        if (Clients::softDeleteClientById($id)) {
            return $response->json([
                'success' => true,
                'message' => 'client deleted successfully!',
                'data' => Clients::allClients(),
            ]);
        }
        return $response->json([
            'success' => false,
            'message' => 'Failed to delete client!',
        ]);
    }
}
```
The above example assumes React-like behaviour (that's why we didn't render a view in the view method, but rather just returned a json response instead).
However, you are not limited to rendering a view if you deem so necessary

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

[![GitHub](https://img.shields.io/badge/GitHub-Profile-blue?logo=github)](https://jos254kenya.github.io/myportfolio/)

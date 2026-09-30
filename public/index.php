<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Dotenv\Dotenv;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists(__DIR__.'/../storage/framework/maintenance.php')) {
    require __DIR__.'/../storage/framework/maintenance.php';
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

// Reset environment if visiting the root URL or login page directly (GET requests only)
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$request_uri = strtok($_SERVER['REQUEST_URI'], '?');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($request_uri === $base_path . '/login') {

        // demo redirect থেকে আসলে cookie clear করবো না
        if (!isset($_GET['demo'])) {
            if (isset($_COOKIE['env_name'])) {
                setcookie('env_name', '', time() - 3600, '/');
                unset($_COOKIE['env_name']);
            }
        }
      
      
    } elseif (in_array($request_uri, [$base_path . '/', $base_path, $base_path . '/index.php'])) {
        // Reset on root URL only if it's a direct entry (no internal referer) AND no demo flag
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ((!$referer || strpos($referer, $host) === false) && !isset($_GET['demo'])) {
            if (isset($_COOKIE['env_name'])) {
                setcookie('env_name', '', time() - 3600, '/');
                unset($_COOKIE['env_name']);
            }
        }
    }
}

// ✅ Now load the environment from cookie if available
if (isset($_COOKIE['env_name']) && file_exists(__DIR__ . '/../' . $_COOKIE['env_name'])) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../', $_COOKIE['env_name']);
    $dotenv->load();
}


/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);


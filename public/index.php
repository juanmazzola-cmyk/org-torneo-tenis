<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// TEMP: limpiar route/view/opcache cache en Ferozo
foreach (glob(__DIR__.'/../bootstrap/cache/routes*.php') as $f) { @unlink($f); }
foreach (glob(__DIR__.'/../storage/framework/views/*.php') as $f) { @unlink($f); }
if (function_exists('opcache_reset')) { opcache_reset(); }

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());

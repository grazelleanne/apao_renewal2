<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Locate the Laravel application
|--------------------------------------------------------------------------
|
| In local development this file lives in <project>/public. On shared
| shared hosting, the contents of public are commonly placed in htdocs while
| the private application remains in a sibling laravel folder. The normal
| project layout and the earlier apao_renewal folder name remain supported.
| LARAVEL_APP_ROOT may be set by the server for any other directory layout.
|
*/
$configuredRoot = $_SERVER['LARAVEL_APP_ROOT'] ?? (getenv('LARAVEL_APP_ROOT') ?: null);
$rootCandidates = array_filter([
    $configuredRoot,
    dirname(__DIR__),
    dirname(__DIR__).'/laravel',
    dirname(__DIR__).'/apao_renewal',
]);

$appRoot = null;

foreach ($rootCandidates as $candidate) {
    $candidate = rtrim((string) $candidate, '/\\');

    if (is_file($candidate.'/vendor/autoload.php')
        && is_file($candidate.'/bootstrap/app.php')) {
        $appRoot = $candidate;
        break;
    }
}

if ($appRoot === null) {
    http_response_code(500);
    error_log('Laravel application root was not found. Configure LARAVEL_APP_ROOT or verify the hosting directory layout.');
    exit('The application is temporarily unavailable.');
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appRoot.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $appRoot.'/bootstrap/app.php';

$app->handleRequest(Request::capture());

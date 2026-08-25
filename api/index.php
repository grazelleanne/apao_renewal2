<?php

// Vercel Functions expose only /tmp as writable storage. Point Laravel's
// runtime files there before the application is bootstrapped.
$storagePath = '/tmp/storage';

foreach ([
    $storagePath.'/app/private',
    $storagePath.'/app/public',
    $storagePath.'/framework/cache/data',
    $storagePath.'/framework/sessions',
    $storagePath.'/framework/views',
    $storagePath.'/logs',
] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
}

$_ENV['LARAVEL_STORAGE_PATH'] = $storagePath;
$_SERVER['LARAVEL_STORAGE_PATH'] = $storagePath;

ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

try {
    require __DIR__.'/../public/index.php';
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);

    if (filter_var($_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL)) {
        header('Content-Type: text/plain; charset=UTF-8');
        echo $exception;
    }
}

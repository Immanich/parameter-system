<?php

/*
 * Vercel entry point (vercel-php runtime). Every request that isn't a static file in
 * public/ is rewritten here (see vercel.json). The function's filesystem is read-only
 * except /tmp, so Laravel's generated caches and compiled views go there.
 */
$defaults = [
    'APP_CONFIG_CACHE' => '/tmp/config.php',
    'APP_EVENTS_CACHE' => '/tmp/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/packages.php',
    'APP_ROUTES_CACHE' => '/tmp/routes.php',
    'APP_SERVICES_CACHE' => '/tmp/services.php',
    'VIEW_COMPILED_PATH' => '/tmp/views',
    'LOG_CHANNEL' => 'stderr',
];

foreach ($defaults as $key => $value) {
    if (getenv($key) === false) {
        putenv("{$key}={$value}");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
}

// Run as if public/index.php was hit directly; otherwise Laravel takes /api (this file's
// folder) as the app's base path and /api/trips would route as /trips.
$_SERVER['SCRIPT_FILENAME'] = realpath(__DIR__.'/../public/index.php');
$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/index.php';

require __DIR__.'/../public/index.php';

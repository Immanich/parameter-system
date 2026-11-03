<?php

/*
 * Vercel entry point (vercel-php runtime). Every request that isn't a static file in
 * public/ is rewritten here (see vercel.json). The function's filesystem is read-only
 * except /tmp, so Laravel's generated caches and compiled views go there.
 */
// Built assets (Vite) are served from this function's own build, so their hashed names
// always match the manifest Laravel reads; Vercel's CDN caches them (immutable).
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (str_starts_with($path, '/build/')) {
    $root = realpath(__DIR__.'/../public/build');
    $file = realpath(__DIR__.'/../public'.rawurldecode($path));

    if (! $root || ! $file || ! str_starts_with($file, $root.'/') || ! is_file($file)) {
        http_response_code(404);
        exit;
    }

    $types = [
        'js' => 'text/javascript', 'mjs' => 'text/javascript', 'css' => 'text/css', 'json' => 'application/json',
        'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'webp' => 'image/webp',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'map' => 'application/json',
    ];
    header('Content-Type: '.($types[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Length: '.filesize($file));
    readfile($file);
    exit;
}

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

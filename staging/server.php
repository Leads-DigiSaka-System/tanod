<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$public = realpath(__DIR__.'/../public');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath($public.$path);

if ($file !== false && str_starts_with($file, $public.DIRECTORY_SEPARATOR)
    && is_file($file) && ! str_starts_with(basename($file), '.')
    && ! in_array(pathinfo($file, PATHINFO_EXTENSION), ['php', 'phar'], true)) {
    return false;
}

if (str_starts_with(getenv('APP_URL') ?: '', 'https://')) {
    $_SERVER['HTTPS'] = 'on';
}

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->booted(function () use ($app): void {
    $app->make('db')->connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT',
        fn (string $date): string => substr($date, 0, 7), 2);
});

$request = Request::capture();
$kernel = $app->make(Kernel::class);
$response = $kernel->handle($request);
$response->headers->set('X-Robots-Tag', 'noindex');
if (getenv('GOOGLE_MAP_KEY') === false
    && str_contains($response->headers->get('Content-Type', ''), 'text/html')
    && is_file($public.'/staging/maps.js')) {
    $stagingAssets = '<link rel="stylesheet" href="/staging/leaflet.css">'
        .'<script src="/staging/leaflet.js"></script><script src="/staging/maps.js"></script>';
    $response->setContent(str_replace('</head>', $stagingAssets.'</head>', $response->getContent()));
}
$response->send();
$kernel->terminate($request, $response);

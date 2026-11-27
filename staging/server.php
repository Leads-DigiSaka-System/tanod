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

$app = require __DIR__.'/bootstrap.php';

$request = Request::capture();
$kernel = $app->make(Kernel::class);
$response = $kernel->handle($request);
$response->headers->set('X-Robots-Tag', 'noindex');
if (! getenv('GOOGLE_MAP_KEY')
    && str_contains($response->headers->get('Content-Type', ''), 'text/html')
    && is_file($public.'/staging/maps.js')) {
    $sampleMap = getenv('TANOD_STAGING_FLEET_MODE') === 'sample' ? 'true' : 'false';
    $stagingAssets = '<link rel="stylesheet" href="/staging/leaflet.css"><script src="/staging/leaflet.js"></script>'
        .'<script src="/staging/maps.js" data-sample-map="'.$sampleMap.'"></script>';
    $response->setContent(str_replace('</head>', $stagingAssets.'</head>', $response->getContent()));
}
$response->send();
$kernel->terminate($request, $response);

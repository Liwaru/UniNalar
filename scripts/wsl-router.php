<?php

// Serve only real public assets; all other paths pass through Laravel.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath(__DIR__.'/../public'.rawurldecode($path));
$public = realpath(__DIR__.'/../public');
if ($file && str_starts_with($file, $public.DIRECTORY_SEPARATOR) && is_file($file)
    && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    return false;
}
require __DIR__.'/../public/index.php';

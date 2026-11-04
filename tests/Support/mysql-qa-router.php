<?php

use Illuminate\Http\Request;

// Only used by the disposable QA PHP server; verify identity before sessions or writes.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$public = realpath(dirname(__DIR__, 2).'/public');
$file = realpath($public.$path);
if ($path !== '/' && $file && str_starts_with($file, $public.DIRECTORY_SEPARATOR) && is_file($file)) {
    return false;
}
$app = require __DIR__.'/mysql-bootstrap.php';
$app->handleRequest(Request::capture());

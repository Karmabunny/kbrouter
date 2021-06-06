<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';

use karmabunny\router\Router;

$router = Router::create();
$router->load([
    '/' => 'index',
    '/{page}' => 'page',
    '/test/{x}/power/{y}' => 'power',
]);

$method = $_SERVER['REQUEST_METHOD'];
$path = '/' . $_GET['_uri'];
$action = $router->find($method, $path);


if (!$action) {
    not_found();
}
else {
    dispatch($action->target, $action->args);
}


function dispatch($fn, $args)
{
    $reflect = new ReflectionFunction($fn);
    $params = [];

    foreach ($reflect->getParameters() as $param) {
        $name = $param->getName();
        $params[] = $args[$name] ?? null;
    }

    return $reflect->invokeArgs($params);
}


function not_found()
{
    header('content-type: text/html');
    http_response_code(404);
    include __DIR__ . '/pages/404.php';
    exit;
}


function index()
{
    header('location: /welcome');
}


function page($page)
{
    $path = realpath(__DIR__ . "/pages/{$page}.php");
    if (!$path) not_found();

    header('content-type: text/html');
    include $path;
    exit;
}


function power($x, $y)
{
    header('content-type: text/plain');
    echo pow($x, $y), PHP_EOL;
    exit;
}

<?php
require __DIR__ . '/vendor/autoload.php';

use karmabunny\router\Router;

function puts($value) {
    echo json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
}

$routes = require __DIR__ . '/tests/routes.php';
$router = Router::create([
    'mode' => 'chunked',
]);
$router->load($routes);

// foreach ($routes as $rule => $target) {
//     puts($router->expandRule($rule));
// }
// die;

// $router->compile();

puts($router->find('GET', '/abc/123/def/456/action/edit/789/end'));
echo PHP_EOL;

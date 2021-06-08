<?php
require __DIR__ . '/../vendor/autoload.php';

use karmabunny\router\Router;

const ITERATIONS = 200000;

function main() {
    $router = Router::create(['mode' => 'single']);
    $router->load(include __DIR__ . '/rules.php');
    test($router);

    $router = Router::create(['mode' => 'chunked']);
    $router->load(include __DIR__ . '/rules.php');
    test($router);

    $router = Router::create(['mode' => 'regex']);
    $router->load(include __DIR__ . '/patterns.php');
    test($router);
}


function test(Router $router)
{
    $name = $router->config->mode;

    timeit("{$name} - first", function() use ($router) {
        $i = 0;
        while ($i++ < ITERATIONS) {
            $router->find('GET', '/static/123');
        }
    });

    timeit("{$name} - 3 vars", function() use ($router) {
        $i = 0;
        while ($i++ < ITERATIONS) {
            $router->find('GET', '/abc/1234/def/5678/edit/qwertyuiop/end');
        }
    });

    timeit("{$name} - last", function() use ($router) {
        $i = 0;
        while ($i++ < ITERATIONS) {
            $router->find('GET', '/last/blah');
        }
    });

    timeit("{$name} - not found", function() use ($router) {
        $i = 0;
        while ($i++ < ITERATIONS) {
            $router->find('GET', '/asdfghjkl');
        }
    });

    echo PHP_EOL;
}


function timeit(string $name, callable $cb)
{
    $time = microtime(true);
    $cb();
    $time = microtime(true) - $time;
    echo sprintf('%s: %.2f seconds', $name, $time), PHP_EOL;
}

main();

<?php

namespace kbtests;

use karmabunny\router\Router;

/**
 *
 */
class RouterSingleTest extends RouterTestCase
{

    public function setUp(): void
    {
        $routes = require __DIR__ . '/rules.php';
        $this->router = Router::create([ 'mode' => Router::MODE_SINGLE ]);
        $this->router->load($routes);
    }

}

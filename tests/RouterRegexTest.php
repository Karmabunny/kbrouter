<?php

namespace kbtests;

use karmabunny\router\Router;

/**
 *
 */
class RouterRegexTest extends RouterTestCase
{

    public function setUp(): void
    {
        $this->routes = require __DIR__ . '/patterns.php';
        $this->router = Router::create([ 'mode' => Router::MODE_REGEX ]);
        $this->router->load($this->routes);
    }

}

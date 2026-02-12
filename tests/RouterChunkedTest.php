<?php

namespace kbtests;

use karmabunny\router\Router;

/**
 *
 */
class RouterChunkedTest extends RouterTestCase
{

    public function setUp(): void
    {
        $this->routes = require __DIR__ . '/rules.php';
        $this->router = Router::create([ 'mode' => Router::MODE_CHUNKED ]);
        $this->router->load($this->routes);
    }

}

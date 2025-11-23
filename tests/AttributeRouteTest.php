<?php

namespace kbtests;

use karmabunny\router\Router;
use kbtests\controllers\AttrTestController;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class AttributeRouteTest extends TestCase
{
    /**
     * @requires PHP >= 8.0
     */
    public function testAllRoutes()
    {
        $router = Router::create(['attrs' => Router::ATTR_ALL]);
        $actual = $router->extractFromAttributes(AttrTestController::class);
        $expected = [
            'GET /' => [AttrTestController::class, 'actionRoot'],
            'GET /test' => [AttrTestController::class, 'actionTest'],
            '/thingo/{etc}' => [AttrTestController::class, 'thingEtc'],
            '/duplicate/{etc}/123' => [AttrTestController::class, 'thingEtc'],
            '/php8/*/only' => [AttrTestController::class, 'eightOnly'],
            '/php8/another' => [AttrTestController::class, 'eightAnother'],
            '/php8/repeated' => [AttrTestController::class, 'eightAnother'],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @requires PHP >= 8.0
     */
    public function testAttributesRoutes()
    {
        $router = Router::create(['attrs' => Router::ATTR_ATTRIBUTES]);
        $actual = $router->extractFromAttributes(AttrTestController::class);
        $expected = [
            '/php8/*/only' => [AttrTestController::class, 'eightOnly'],
            '/php8/another' => [AttrTestController::class, 'eightAnother'],
            '/php8/repeated' => [AttrTestController::class, 'eightAnother'],
        ];

        $this->assertEquals($expected, $actual);
    }


    public function testDocRoutes()
    {
        $router = Router::create(['attrs' => Router::ATTR_DOCS]);
        $actual = $router->extractFromAttributes(AttrTestController::class);
        $expected = [
            'GET /' => [AttrTestController::class, 'actionRoot'],
            'GET /test' => [AttrTestController::class, 'actionTest'],
            '/thingo/{etc}' => [AttrTestController::class, 'thingEtc'],
            '/duplicate/{etc}/123' => [AttrTestController::class, 'thingEtc'],
        ];

        $this->assertEquals($expected, $actual);
    }

}

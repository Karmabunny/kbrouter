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
    public function testAttributeRoutes()
    {
        $actual = Router::extractFromAttributes(AttrTestController::class);
        $expected = [
            'GET /test' => [AttrTestController::class, 'actionTest'],
            '/thingo/{etc}' => [AttrTestController::class, 'thingEtc'],
            '/duplicate/{etc}/123' => [AttrTestController::class, 'thingEtc'],
            '/php8/*/only' => [AttrTestController::class, 'eightOnly'],
            '/php8/another' => [AttrTestController::class, 'eightAnother'],
            '/php8/repeated' => [AttrTestController::class, 'eightAnother'],
        ];

        $this->assertEquals($expected, $actual);
    }


    public function testDocRoutes()
    {
        $actual = Router::extractFromAttributes(AttrTestController::class);
        $expected = [
            'GET /test' => [AttrTestController::class, 'actionTest'],
            '/thingo/{etc}' => [AttrTestController::class, 'thingEtc'],
            '/duplicate/{etc}/123' => [AttrTestController::class, 'thingEtc'],
        ];

        $this->assertEquals($expected, $actual);
    }

}

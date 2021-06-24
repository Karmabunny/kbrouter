<?php

namespace kbtests;

use karmabunny\router\Route;
use karmabunny\router\Router;
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
        $actual = Router::extractFromAttributes(TestController::class);
        $expected = [
            'GET /test' => [TestController::class, 'actionTest'],
            '/thingo/{etc}' => [TestController::class, 'thingEtc'],
            '/duplicate/{etc}/123' => [TestController::class, 'thingEtc'],
            '/php8/*/only' => [TestController::class, 'eightOnly'],
            '/php8/another' => [TestController::class, 'eightAnother'],
            '/php8/repeated' => [TestController::class, 'eightAnother'],
        ];

        $this->assertEquals($expected, $actual);
    }


    /**
     * @requires PHP < 8.0
     */
    public function testDocRoutes()
    {
        $actual = Router::extractFromAttributes(TestController::class);
        $expected = [
            'GET /test' => [TestController::class, 'actionTest'],
            '/thingo/{etc}' => [TestController::class, 'thingEtc'],
            '/duplicate/{etc}/123' => [TestController::class, 'thingEtc'],
        ];

        $this->assertEquals($expected, $actual);
    }

}


class TestController
{
    /**
     * @route GET /test
     */
    public function actionTest() {}

    /**
     * @route /thingo/{etc}
     * @route /duplicate/{etc}/123
     */
    public function thingEtc(string $etc) {}

    #[Route('/php8/*/only')]
    public function eightOnly() {}

    #[Route('/php8/another')]
    #[Route('/php8/repeated')]
    public function eightAnother() {}
}

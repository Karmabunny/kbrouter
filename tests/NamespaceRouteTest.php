<?php

namespace kbtests;

use karmabunny\router\Router;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class NamespaceRouteTest extends TestCase
{
    public function testRoutes()
    {
        $actual = Router::extractFromNamespaces(NsTestController::class);

        $expected = [
            'ACTION /kbtests/ns-test/test' => [NsTestController::class, 'test'],
            'ACTION /kbtests/ns-test/thing-etc/{etc}/{ooh}' => [NsTestController::class, 'thingEtc'],
            'ACTION /kbtests/ns-test/get-x-m-l-result/{input}' => [NsTestController::class, 'getXMLResult'],
            'ACTION /kbtests/ns-test/big-long-method-name' => [NsTestController::class, 'big_long_method_name'],
            'ACTION /kbtests/ns-test/static-action' => [NsTestController::class, 'staticAction'],
            'ACTION /kbtests/ns-test/acceptable-args/{num}' => [NsTestController::class, 'acceptableArgs'],
        ];

        $this->assertEquals($expected, $actual);
    }

}


class NsTestController
{
    public function test() {}

    public function thingEtc(string $etc, int $ooh) {}

    public function getXMLResult($input) {}

    public function big_long_method_name() {}

    public static function staticAction() {}

    public static function acceptableArgs(float $num, array $config = null, ?object $context) {}

    // Ignored
    public function _fakePrivate() {}

    // Ignored
    public function variadic(...$args) {}

    // Ignored
    public function badArgument(object $target) {}

    // Ignored
    protected function privateMethod() {}
}

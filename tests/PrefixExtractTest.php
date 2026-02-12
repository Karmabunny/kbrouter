<?php

namespace kbtests;

use karmabunny\router\Router;
use kbtests\controllers\AttrTestController;
use kbtests\controllers\NsTestController;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class PrefixExtractTest extends TestCase
{

    public function testPrefixDocs()
    {
        $router = Router::create();
        $actual = $router->extractFromAttributes(AttrTestController::class, 'prefix');

        $expected = [
            'GET prefix' => [AttrTestController::class, 'actionRoot'],
            'GET prefix/test' => [AttrTestController::class, 'actionTest'],
            'prefix/thingo/{etc}' => [AttrTestController::class, 'thingEtc'],
            'prefix/thingo/test' => [AttrTestController::class, 'thingTest'],
            'prefix/duplicate/{etc}/123' => [AttrTestController::class, 'thingEtc'],
        ];

        if (PHP_VERSION_ID >= 80000) {
            $expected['prefix/php8/*/only'] = [AttrTestController::class, 'eightOnly'];
            $expected['prefix/php8/another'] = [AttrTestController::class, 'eightAnother'];
            $expected['prefix/php8/repeated'] = [AttrTestController::class, 'eightAnother'];
        }

        $expected['POST prefix/thingo/{etc}'] = [AttrTestController::class, 'thingPost'];
        $expected['GET prefix/thingo/{etc}'] = [AttrTestController::class, 'thingGet'];

        $this->assertEquals($expected, $actual);
    }


    public function testPrefixDocsAgain()
    {
        // This time with prefix and suffix slashes.
        $router = Router::create();
        $actual = $router->extractFromAttributes(AttrTestController::class, '/prefix/');

        $expected = [
            'GET /prefix' => [AttrTestController::class, 'actionRoot'],
            'GET /prefix/test' => [AttrTestController::class, 'actionTest'],
            '/prefix/thingo/{etc}' => [AttrTestController::class, 'thingEtc'],
            '/prefix/thingo/test' => [AttrTestController::class, 'thingTest'],
            '/prefix/duplicate/{etc}/123' => [AttrTestController::class, 'thingEtc'],
        ];

        if (PHP_VERSION_ID >= 80000) {
            $expected['/prefix/php8/*/only'] = [AttrTestController::class, 'eightOnly'];
            $expected['/prefix/php8/another'] = [AttrTestController::class, 'eightAnother'];
            $expected['/prefix/php8/repeated'] = [AttrTestController::class, 'eightAnother'];
        }

        $expected['POST /prefix/thingo/{etc}'] = [AttrTestController::class, 'thingPost'];
        $expected['GET /prefix/thingo/{etc}'] = [AttrTestController::class, 'thingGet'];

        $this->assertEquals($expected, $actual);
    }


    public function testPrefixNamespaces()
    {
        $router = Router::create();
        $actual = $router->extractFromNamespaces(NsTestController::class, '/prefix/');
        $actual = [ key($actual) => current($actual) ];

        $expected = [
            'ACTION /prefix/kbtests/ns-test/test' => [NsTestController::class, 'test'],
        ];

        $this->assertEquals($expected, $actual);
    }


    public function testPrefixNamespacesAgain()
    {
        // This time with no trailing slash.
        $router = Router::create();
        $actual = $router->extractFromNamespaces(NsTestController::class, '/another');
        $actual = [ key($actual) => current($actual) ];

        $expected = [
            'ACTION /another/kbtests/ns-test/test' => [NsTestController::class, 'test'],
        ];

        $this->assertEquals($expected, $actual);
    }


    public function testPrefixNested()
    {
        $router = Router::create([
            'extract' => 'namespaces|short|prefixes|nested',
        ]);

        $router->load([
            'prefix' => [
                NsTestController::class,
                AttrTestController::class,
            ],
        ]);

        $expected = [
            'ACTION prefix/ns-test/test' => [NsTestController::class, 'test'],
            'ACTION prefix/ns-test/thing-etc/{etc}/{ooh}' => [NsTestController::class, 'thingEtc'],
            'ACTION prefix/ns-test/get-x-m-l-result/{input}' => [NsTestController::class, 'getXMLResult'],
            'ACTION prefix/ns-test/big-long-method-name' => [NsTestController::class, 'big_long_method_name'],
            'ACTION prefix/ns-test/optionals/{one}' => [NsTestController::class, 'optionals'],
            'ACTION prefix/ns-test/optionals/{one}/{two}' => [NsTestController::class, 'optionals'],
            'ACTION prefix/ns-test/optionals/{one}/{two}/{three}' => [NsTestController::class, 'optionals'],
            'ACTION prefix/ns-test/static-action' => [NsTestController::class, 'staticAction'],
            'ACTION prefix/ns-test/acceptable-args/{num}' => [NsTestController::class, 'acceptableArgs'],

            'ACTION prefix/attr-test/root' => [AttrTestController::class, 'actionRoot'],
            'ACTION prefix/attr-test/test' => [AttrTestController::class, 'actionTest'],
            'ACTION prefix/attr-test/thing-etc/{etc}' => [AttrTestController::class, 'thingEtc'],
            'ACTION prefix/attr-test/thing-test' => [AttrTestController::class, 'thingTest'],
            'ACTION prefix/attr-test/eight-only' => [AttrTestController::class, 'eightOnly'],
            'ACTION prefix/attr-test/eight-another' => [AttrTestController::class, 'eightAnother'],
            'ACTION prefix/attr-test/thing-post/{etc}' => [AttrTestController::class, 'thingPost'],
            'ACTION prefix/attr-test/thing-get/{etc}' => [AttrTestController::class, 'thingGet'],
        ];

        $actual = $router->routes;
        $this->assertEquals($expected, $actual);
    }
}

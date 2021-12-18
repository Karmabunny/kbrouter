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
        $actual = Router::extractFromAttributes(AttrTestController::class, 'prefix');

        $expected = [
            'GET prefix/test' => [AttrTestController::class, 'actionTest'],
            'prefix/thingo/{etc}' => [AttrTestController::class, 'thingEtc'],
            'prefix/duplicate/{etc}/123' => [AttrTestController::class, 'thingEtc'],
        ];

        $this->assertEquals($expected, $actual);
    }


    public function testPrefixDocsAgain()
    {
        // This time with prefix and suffix slashes.
        $actual = Router::extractFromAttributes(AttrTestController::class, '/prefix/');

        $expected = [
            'GET /prefix/test' => [AttrTestController::class, 'actionTest'],
            '/prefix/thingo/{etc}' => [AttrTestController::class, 'thingEtc'],
            '/prefix/duplicate/{etc}/123' => [AttrTestController::class, 'thingEtc'],
        ];

        $this->assertEquals($expected, $actual);
    }


    public function testPrefixNamespaces()
    {
        $actual = Router::extractFromNamespaces(NsTestController::class, '/prefix/');
        $actual = [ key($actual) => current($actual) ];

        $expected = [
            'ACTION /prefix/kbtests/ns-test/test' => [NsTestController::class, 'test'],
        ];

        $this->assertEquals($expected, $actual);
    }


    public function testPrefixNamespacesAgain()
    {
        // This time with no trailing slash.
        $actual = Router::extractFromNamespaces(NsTestController::class, '/another');
        $actual = [ key($actual) => current($actual) ];

        $expected = [
            'ACTION /another/kbtests/ns-test/test' => [NsTestController::class, 'test'],
        ];

        $this->assertEquals($expected, $actual);
    }
}

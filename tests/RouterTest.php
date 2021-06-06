<?php

namespace kbtests;

use karmabunny\router\Router;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class RouterTest extends TestCase
{
    public function testExtractNames()
    {
        $routes = require __DIR__ . '/rules.php';


    }


    public function testFillValues()
    {

    }


    public function testAutoRoute()
    {

    }


    public function testNormalize()
    {
        $matches = [
            'm1' => 'abc',
            'abc',
            'wild',
            'm2' => 'def',
            'def',
        ];

        $expected = [
            'm1' => 'abc',
            'm2' => 'def',
            'wild',
        ];

        $actual = Router::normalizeMatches($matches);
        $this->assertEquals($expected, $actual);


        // $matches = [
        //     'm1' => 'abc',
        //     'abc',
        //     'abc',
        //     'wild',
        //     'm2' => 'abc',
        //     'abc',
        // ];

        // $expected = [
        //     'm1' => 'abc',
        //     'm2' => 'abc',
        //     'abc',
        //     'wild',
        // ];

        // $actual = Router::normalizeMatches($matches);
        // $this->assertEquals($expected, $actual);
    }
}

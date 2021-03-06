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
        // No args.
        $actual = Router::extractRuleNames('/get/123');
        $expected = [];
        $this->assertEquals($expected, $actual);

        // One arg.
        $actual = Router::extractRuleNames('/abc/{var1}');
        $expected = ['var1'];
        $this->assertEquals($expected, $actual);

        // Invalid rule template.
        $actual = Router::extractRuleNames('/abc/{var/1}');
        $expected = [];
        $this->assertEquals($expected, $actual);

        // Invalid rule template.
        $actual = Router::extractRuleNames('/abc/{123}');
        $expected = [];
        $this->assertEquals($expected, $actual);

        // Nested arg.
        $actual = Router::extractRuleNames('/abc/{var1}/123');
        $expected = ['var1'];
        $this->assertEquals($expected, $actual);

        // Lots of args.
        $actual = Router::extractRuleNames('/abc/{var1}/def/{var2}/action/edit/{var3}/end');
        $expected = ['var1', 'var2', 'var3'];
        $this->assertEquals($expected, $actual);
    }


    public function testFillValues()
    {
        // No args.
        $actual = Router::fillRuleValues('/get/123', ['123' => 'blah']);
        $expected = '/get/123';
        $this->assertEquals($expected, $actual);

        // One arg.
        $actual = Router::fillRuleValues('/get/{var1}', [
            'not-to-get-confused',
            'var1' => 'blah',
        ]);
        $expected = '/get/blah';
        $this->assertEquals($expected, $actual);

        // Bunch of args.
        $actual = Router::fillRuleValues('/get/{var1}/{hello}/etc/{world}/edit', [
            'var1' => '123',
            'hello' => 'foo',
            'world' => 'bar',
        ]);
        $expected = '/get/123/foo/etc/bar/edit';
        $this->assertEquals($expected, $actual);
    }


    // TODO
    // public function testAutoRoute()
    // {
    // }


    public function testNormalize()
    {
        $matches = [];
        preg_match('/(?<m1>abc)(wild)(stuff)(?<m2>def)/', 'abcwildstuffdef', $matches);
        array_shift($matches);

        $expected = [
            'm1' => 'abc',
            'm2' => 'def',
            'wild',
            'stuff',
        ];

        $actual = Router::normalizeMatches($matches);
        $this->assertEquals($expected, $actual);

        // Testing duplicate stuff.
        $matches = [];
        preg_match('/(?<m1>abc)(?<m2>def)(abc)(wild)(wild)(?<m3>abc)/', 'abcdefabcwildwildabc', $matches);
        array_shift($matches);

        $expected = [
            'm1' => 'abc',
            'm2' => 'def',
            'm3' => 'abc',
            'abc',
            'wild',
            'wild',
        ];

        $actual = Router::normalizeMatches($matches);
        $this->assertEquals($expected, $actual);
    }
}

<?php

namespace kbtests;

use karmabunny\router\Router;
use kbtests\controllers\AttrTestController;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SortRulesTest extends TestCase
{


    public static function dataRules(): array
    {
        return [
            'w'     => [10,'/test/*',           ['method' => false, 'variable' => false, 'wildcard' => true]],
            'w1'    => [9, '/asdf/*',           ['method' => false, 'variable' => false, 'wildcard' => true]],
            'w2'    => [8, '/testlong/*',       ['method' => false, 'variable' => false, 'wildcard' => true]],
            'w3'    => [7, '/testlonger/*',     ['method' => false, 'variable' => false, 'wildcard' => true]],
            'm w'   => [3, 'GET /test/*',       ['method' => true,  'variable' => false, 'wildcard' => true]],
            'v'     => [6, '/test/{etc}',       ['method' => false, 'variable' => true,  'wildcard' => false]],
            'm v w' => [2, 'GET /test/{etc}/*', ['method' => true,  'variable' => true,  'wildcard' => true]],
            'm v'   => [1, 'GET /test/{etc}',   ['method' => true,  'variable' => true,  'wildcard' => false]],
            'x'     => [5, '/test/thing',       ['method' => false, 'variable' => false, 'wildcard' => false]],
            'm'     => [0, 'get /test/thing',   ['method' => true,  'variable' => false, 'wildcard' => false]],
            'o'     => [4, 'ANOTHER /test',     ['method' => false, 'variable' => false, 'wildcard' => false]],
        ];
    }


    /** @dataProvider dataRules */
    public function testRuleInspector(int $sort, string $rule, array $expected)
    {
        $router = Router::create();
        $inspector = $router->getRuleInspector();

        $actual = $inspector($rule);
        unset($actual['rule']);

        $this->assertEquals($expected, $actual);
    }


    public function testSortRules()
    {
        $router = Router::create();
        $rules = self::dataRules();

        $rules = array_combine(
            array_column($rules, 1),
            array_column($rules, 0)
        );

        // Expected sort.
        $expected = $rules;
        asort($expected);
        $expected = array_keys($expected);

        // Actual sort.
        $actual = $rules;
        $router->sortAttributes($actual);
        $actual = array_keys($actual);

        $this->assertEquals($expected, $actual);
    }


    public function testUnsortedRoutes()
    {
        $router = Router::create();
        // Disable attribute sort.
        $router->config->extract &= ~Router::EXTRACT_SORT_ATTRIBUTES;
        $router->config->attrs = Router::ATTR_DOCS;

        $router->load([
            AttrTestController::class,
        ]);

        // Easy one.
        $action = $router->find('GET', '/');
        $this->assertNotNull($action);
        $this->assertEquals('GET /', $action->rule);

        // Expected match.
        $action = $router->find('PATCH', '/thingo/other');
        $this->assertNotNull($action);
        $this->assertEquals('/thingo/{etc}', $action->rule);

        // Picks up the first rule, not the best rule.
        $action = $router->find('POST', '/thingo/test');
        $this->assertNotNull($action);
        $this->assertEquals('/thingo/{etc}', $action->rule);

        // And again.
        $action = $router->find('GET', '/thingo/test');
        $this->assertNotNull($action);
        $this->assertEquals('/thingo/{etc}', $action->rule);

        // First rule wins again, rather the explicit rule.
        $action = $router->find('PATCH', '/thingo/test');
        $this->assertNotNull($action);
        $this->assertEquals('/thingo/{etc}', $action->rule);
    }


    public function testSortedRoutes()
    {
        $router = Router::create();
        // Force enable attribute sort.
        $router->config->extract |= Router::EXTRACT_SORT_ATTRIBUTES;
        $router->config->attrs = Router::ATTR_DOCS;

        $router->load([
            AttrTestController::class,
        ]);

        // Easy one.
        $action = $router->find('GET', '/');
        $this->assertNotNull($action);
        $this->assertEquals('GET /', $action->rule);

        // Expected match.
        $action = $router->find('PATCH', '/thingo/other');
        $this->assertNotNull($action);
        $this->assertEquals('/thingo/{etc}', $action->rule);

        // Explicit methods get priority.
        $action = $router->find('POST', '/thingo/test');
        $this->assertNotNull($action);
        $this->assertEquals('POST /thingo/{etc}', $action->rule);

        // And again.
        $action = $router->find('GET', '/thingo/test');
        $this->assertNotNull($action);
        $this->assertEquals('GET /thingo/{etc}', $action->rule);

        // Explicit rule wins over non-method variable rule.
        $action = $router->find('PATCH', '/thingo/test');
        $this->assertNotNull($action);
        $this->assertEquals('/thingo/test', $action->rule);
    }
}

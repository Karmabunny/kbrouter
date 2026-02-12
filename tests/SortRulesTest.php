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
            'w'     => [7, '/test/*',           ['method' => false, 'variable' => false, 'wildcard' => true]],
            'm w'   => [3, 'GET /test/*',       ['method' => true,  'variable' => false, 'wildcard' => true]],
            'v'     => [6, '/test/{etc}',       ['method' => false, 'variable' => true,  'wildcard' => false]],
            'm v w' => [2, 'GET /test/{etc}/*', ['method' => true,  'variable' => true,  'wildcard' => true]],
            'm v'   => [1, 'GET /test/{etc}',   ['method' => true,  'variable' => true,  'wildcard' => false]],
            'x'     => [4, '/test/thing',       ['method' => false, 'variable' => false, 'wildcard' => false]],
            'm'     => [0, 'GET /test/thing',   ['method' => true,  'variable' => false, 'wildcard' => false]],
            'o'     => [5, 'ANOTHER /test',     ['method' => false, 'variable' => false, 'wildcard' => false]],
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

        // Expected sort.
        $expected = $rules;
        uasort($expected, function($a, $b) {
            return $a[0] <=> $b[0];
        });
        $expected = array_column($expected, 1);

        // Actual sort.
        $sorted = array_column($rules, 1);
        usort($sorted, [$router, 'sortAttributes']);

        $this->assertEquals($expected, $sorted);
    }


    public function testUnsortedRoutes()
    {
        $router = Router::create();
        // Disable attribute sort.
        $router->config->extract ^= Router::EXTRACT_SORT_ATTRIBUTES;
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

        // explict methods get priority.
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

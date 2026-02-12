<?php

namespace kbtests;

use karmabunny\router\Router;
use PHPUnit\Framework\TestCase;

/**
 *
 */
abstract class RouterTestCase extends TestCase
{
    /** @var array */
    public $routes;

    /** @var Router */
    public $router;


    public function testNotFound()
    {
        $action = $this->router->find('GET', '/asdf');
        $this->assertNull($action);
    }

    public function testStatic()
    {
        foreach (['GET', 'POST', 'DELETE', 'UHHH'] as $method) {
            $action = $this->router->find($method, '/static/123');

            $this->assertNotNull($action);
            $this->assertEquals('/static/123', $action->rule);
            $this->assertEquals('static route', $action->target);
            $this->assertEquals($method, $action->method);
            $this->assertEquals([], $action->args);
        }
    }


    public function testSingleMethod()
    {
        $action = $this->router->find('GET', '/get/123');
        $this->assertNotNull($action);
        $this->assertEquals('get route', $action->target);

        $action = $this->router->find('POST', '/get/123');
        $this->assertNull($action);

        // Assume same behaviour for post.
    }


    public function testMultiMethod()
    {
        $action = $this->router->find('GET', '/multi/123');
        $this->assertNotNull($action);
        $this->assertEquals('multi route - get', $action->target);

        $action = $this->router->find('PUT', '/multi/123');
        $this->assertNotNull($action);
        $this->assertEquals('multi route - put', $action->target);

        $action = $this->router->find('DELETE', '/multi/123');
        $this->assertNull($action);
    }


    public function testCaseInsensitive()
    {
        $router = Router::create($this->router->config);
        $router->config->case_insensitive = false;
        $router->load($this->routes);

        // Good.
        $action = $router->find('GET', '/get/123');
        $this->assertNotNull($action);
        $this->assertEquals('get route', $action->target);

        // Bad.
        $action = $router->find('GET', '/GET/123');
        $this->assertNull($action);

        // Methods are not case sensitive.
        $action = $router->find('get', '/get/123');
        $this->assertNotNull($action);
        $this->assertEquals('get route', $action->target);

        $router = Router::create($router->config);
        $router->config->case_insensitive = true;
        $router->load($this->routes);

        // Still good.
        $action = $router->find('GET', '/get/123');
        $this->assertNotNull($action);
        $this->assertEquals('get route', $action->target);

        // Now this works.
        $action = $router->find('GET', '/GET/123');
        $this->assertNotNull($action);
        $this->assertEquals('get route', $action->target);

        // Also still good.
        $action = $router->find('get', '/GeT/123');
        $this->assertNotNull($action);
        $this->assertEquals('get route', $action->target);
    }


    public function testVariables()
    {
        $action = $this->router->find('GET', '/abc/12345');
        $this->assertNotNull($action);
        $this->assertEquals('route with variable', $action->target);
        $this->assertEquals(['var1' => '12345'], $action->args);

        $action = $this->router->find('GET', '/abc/middle/123');
        $this->assertNotNull($action);
        $this->assertEquals('route with a nested variable', $action->target);
        $this->assertEquals(['var1' => 'middle'], $action->args);

        $action = $this->router->find('GET', '/abc/123/567/678');
        $this->assertNull($action);

        $action = $this->router->find('GET', '/abc/one and/two hey');
        $this->assertNotNull($action);
        $this->assertEquals('route with two variables', $action->target);
        $this->assertEquals([
            'var1' => 'one and',
            'var2' => 'two hey',
        ], $action->args);

        $action = $this->router->find('GET', '/abc/123/def/456/action/edit/789/end');
        $this->assertNotNull($action);
        $this->assertEquals('big messy route', $action->target);
        $this->assertEquals([
            'var1' => '123',
            'var2' => '456',
            'var3' => '789',
        ], $action->args);

        $action = $this->router->find('GET', '/3rd-last');
        $this->assertNotNull($action);
        $this->assertEquals('last - 2', $action->target);

        $action = $this->router->find('GET', '/penultimate/hello');
        $this->assertNotNull($action);
        $this->assertEquals('last - 1', $action->target);

        $action = $this->router->find('GET', '/last/stuff');
        $this->assertNotNull($action);
        $this->assertEquals('last - 0', $action->target);
        $this->assertEquals(['world' => 'stuff'], $action->args);
    }


    public function testWildcards()
    {
        $action = $this->router->find('GET', '/abc/wild/whatever/you/want');
        $this->assertNotNull($action);
        $this->assertEquals('wildcard route', $action->target);
        $this->assertEquals(['whatever/you/want'], $action->args);

        $action = $this->router->find('GET', '/abc/pre/thing/guff/and/stuff');
        $this->assertNotNull($action);
        $this->assertEquals('wildcard route w/ pre', $action->target);
        $this->assertEquals([
            'pre' => 'thing',
            'guff/and/stuff',
        ], $action->args);

        $action = $this->router->find('GET', '/abc/post/blah/blah/hello');
        $this->assertNotNull($action);
        $this->assertEquals('wildcard route w/ post', $action->target);
        $this->assertEquals([
            'post' => 'hello',
            'blah/blah',
        ], $action->args);
    }



    public function testZeroValue()
    {
        $action = $this->router->find('GET', '/abc/0');
        $this->assertNotNull($action);
        $this->assertEquals('route with variable', $action->target);
        $this->assertEquals(['var1' => '0'], $action->args);

        $action = $this->router->find('GET', '/abc/false');
        $this->assertEquals('route with variable', $action->target);
        $this->assertEquals(['var1' => 'false'], $action->args);

        $action = $this->router->find('GET', '/abc/post/0/var');
        $this->assertEquals('wildcard route w/ post', $action->target);
        $this->assertEquals(['post' => 'var', '0'], $action->args);
    }
}

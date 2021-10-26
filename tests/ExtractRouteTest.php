<?php

namespace kbtests;

use karmabunny\router\Router;
use karmabunny\router\RouterConfig;
use kbtests\controllers\AttrTestController;
use kbtests\controllers\NsTestController;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ExtractRouteTest extends TestCase
{

    const ROUTES = [
        'GET /a/test/{route}' => 'hi there',
        'PUT /another' => 'senior kenobi',
    ];


    public function testExtractConfig()
    {
        $config = new RouterConfig([ 'extract' => null ]);
        $this->assertEquals(Router::EXTRACT_NONE, $config->extract);
        $this->assertNotContains('ACTION', $config->methods);

        $config = new RouterConfig([ 'extract' => 'namespaces' ]);
        $this->assertEquals(Router::EXTRACT_NAMESPACES, $config->extract);
        $this->assertContains('ACTION', $config->methods);

        $config = new RouterConfig([ 'extract' => 'attributes' ]);
        $this->assertEquals(Router::EXTRACT_ATTRIBUTES, $config->extract);
        $this->assertNotContains('ACTION', $config->methods);

        $config = new RouterConfig([ 'extract' => 'all' ]);
        $this->assertEquals(Router::EXTRACT_ALL, $config->extract);
        $this->assertContains('ACTION', $config->methods);

        $config = new RouterConfig([ 'extract' => Router::EXTRACT_NAMESPACES ]);
        $this->assertEquals(Router::EXTRACT_NAMESPACES, $config->extract);
        $this->assertContains('ACTION', $config->methods);

        $config = new RouterConfig([ 'extract' => Router::EXTRACT_ATTRIBUTES ]);
        $this->assertEquals(Router::EXTRACT_ATTRIBUTES, $config->extract);
        $this->assertNotContains('ACTION', $config->methods);

        $config = new RouterConfig([ 'extract' => Router::EXTRACT_ALL ]);
        $this->assertEquals(Router::EXTRACT_ALL, $config->extract);
        $this->assertContains('ACTION', $config->methods);
    }


    public function testExtractNone()
    {
        $routes = self::ROUTES;
        $routes[] = NsTestController::class;
        $routes[] = AttrTestController::class;

        $router = Router::create([
            'extract' => Router::EXTRACT_NONE,
        ]);
        $router->load($routes);

        $this->assertCount(2, $router->routes);
    }


    public function testExtractNamespaces()
    {
        $routes = self::ROUTES;
        $routes[] = NsTestController::class;

        $router = Router::create([
            'extract' => Router::EXTRACT_NAMESPACES,
        ]);
        $router->load($routes);

        $this->assertCount(8, $router->routes);

        $action = $router->find('GET', '/a/test/one');
        $this->assertNotNull($action);
        $this->assertEquals('hi there', $action->target);

        $action = $router->find('PUT', '/another');
        $this->assertNotNull($action);
        $this->assertEquals('senior kenobi', $action->target);

        $action = $router->find('ACTION', '/kbtests/ns-test/test');
        $this->assertNotNull($action);
        $this->assertEquals([NsTestController::class, 'test'], $action->target);

        $action = $router->find('ACTION', '/kbtests/ns-test/test');
        $this->assertNotNull($action);
        $this->assertEquals([NsTestController::class, 'test'], $action->target);
    }

    public function testExtractAttributes()
    {
        $routes = self::ROUTES;
        $routes[] = AttrTestController::class;

        $router = Router::create([
            'extract' => Router::EXTRACT_ATTRIBUTES,
        ]);
        $router->load($routes);

        if (PHP_VERSION_ID >= 80000) {
            $this->assertCount(8, $router->routes);
        }
        else {
            $this->assertCount(5, $router->routes);
        }

        $action = $router->find('GET', '/a/test/one');
        $this->assertNotNull($action);
        $this->assertEquals('hi there', $action->target);

        $action = $router->find('PUT', '/another');
        $this->assertNotNull($action);
        $this->assertEquals('senior kenobi', $action->target);

        $action = $router->find('POST', '/test');
        $this->assertNull($action);

        $action = $router->find('GET', '/test');
        $this->assertNotNull($action);
        $this->assertEquals([AttrTestController::class, 'actionTest'], $action->target);

        $action = $router->find('DELETE', '/thingo/blahblahblah');
        $this->assertNotNull($action);
        $this->assertEquals([AttrTestController::class, 'thingEtc'], $action->target);
    }


    public function testExtractAll()
    {
        $routes = self::ROUTES;
        $routes[] = AttrTestController::class;
        $routes[] = NsTestController::class;

        $router = Router::create([
            'extract' => 'all',
        ]);
        $router->load($routes);

        // static.
        $count = 2;

        // attributes.
        if (PHP_VERSION_ID >= 80000) {
            $count += 6;
        }
        else {
            $count += 3;
        }

        // namespaces, from ns-test.
        $count += 6;

        // namespaces, from attr-test.
        $count += 4;

        $this->assertCount($count, $router->routes);
    }
}

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
        $this->assertEquals(Router::EXTRACT_ALL | Router::EXTRACT_WITH_PREFIXES, $config->extract);
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

        // Bad config - convert must be paired with mode_regex
        $config = new RouterConfig([
            'extract' => Router::EXTRACT_NAMESPACES | Router::EXTRACT_CONVERT_REGEX,
            'mode' => Router::MODE_SINGLE,
        ]);

        $this->assertEquals(Router::MODE_SINGLE, $config->mode);
        $this->assertTrue((bool) ($config->extract & Router::EXTRACT_NAMESPACES));
        $this->assertFalse((bool) ($config->extract & Router::EXTRACT_CONVERT_REGEX));
        $this->assertContains('ACTION', $config->methods);

        // Good config.
        $config = new RouterConfig([
            'extract' => Router::EXTRACT_NAMESPACES | Router::EXTRACT_CONVERT_REGEX,
            'mode' => Router::MODE_REGEX,
        ]);
        $this->assertEquals(Router::MODE_REGEX, $config->mode);
        $this->assertTrue((bool) ($config->extract & Router::EXTRACT_NAMESPACES));
        $this->assertTrue((bool) ($config->extract & Router::EXTRACT_CONVERT_REGEX));
    }


    public function testExtractNone()
    {
        $router = Router::create([
            'extract' => Router::EXTRACT_NONE,
        ]);
        $router->load([
            'GET /a/test/{route}' => 'hi there',
            'PUT /another' => 'senior kenobi',
            NsTestController::class,
            AttrTestController::class,
        ]);

        $this->assertCount(2, $router->routes);
    }


    public function testExtractNamespaces()
    {
        $router = Router::create([
            'extract' => Router::EXTRACT_NAMESPACES,
        ]);
        $router->load([
            'GET /a/test/{route}' => 'hi there',
            'PUT /another' => 'senior kenobi',
            NsTestController::class,
            '/prefix' => NsTestController::class,
        ]);

        $this->assertCount(9, $router->routes);

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

        // This is the 'prefix' rule not being prefixed.
        // Because it's not enabled with EXTRACT_WITH_PREFIXES.
        $action = $router->find('GET', '/prefix');
        $this->assertNotNull($action);
        $this->assertEquals(NsTestController::class, $action->target);

    }


    public function testExtractAttributes()
    {
        $router = Router::create([
            'extract' => Router::EXTRACT_ATTRIBUTES,
        ]);
        $router->load([
            'GET /a/test/{route}' => 'hi there',
            'PUT /another' => 'senior kenobi',
            AttrTestController::class,
            '/prefix' => AttrTestController::class,
        ]);

        if (PHP_VERSION_ID >= 80000) {
            $this->assertCount(9, $router->routes);
        }
        else {
            $this->assertCount(6, $router->routes);
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

        // This is the 'prefix' rule not being prefixed.
        // Because it's not enabled with EXTRACT_WITH_PREFIXES.
        $action = $router->find('GET', '/prefix');
        $this->assertNotNull($action);
        $this->assertEquals(AttrTestController::class, $action->target);
    }


    public function testExtractAll()
    {
        $router = Router::create([
            'extract' => Router::EXTRACT_ALL,
        ]);
        $router->load([
            'GET /a/test/{route}' => 'hi there',
            'PUT /another' => 'senior kenobi',
            AttrTestController::class,
            NsTestController::class,
        ]);

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


    public function testExtractWithPrefixes()
    {
        $router = Router::create([
            'extract' => 'all',
        ]);
        $router->load([
            'GET /a/test/{route}' => 'hi there',
            'PUT /another' => 'senior kenobi',
            AttrTestController::class,
            NsTestController::class,
            '/prefix1' => AttrTestController::class,
            '/prefix2' => NsTestController::class,
        ]);

        // static.
        $count = 2;

        // attributes.
        if (PHP_VERSION_ID >= 80000) {
            $count += 6 * 2;
        }
        else {
            $count += 3 * 2;
        }

        // namespaces, from ns-test.
        $count += 6 * 2;

        // namespaces, from attr-test.
        $count += 4 * 2;

        $this->assertCount($count, $router->routes);

        $action = $router->find('GET', '/prefix1');
        $this->assertNull($action);

        $action = $router->find('GET', '/prefix2');
        $this->assertNull($action);

        $action = $router->find('GET', '/prefix1/test');
        $this->assertNotNull($action);
        $this->assertEquals([AttrTestController::class, 'actionTest'], $action->target);

        $action = $router->find('GET', '/prefix1/thingo/123');
        $this->assertNotNull($action);
        $this->assertEquals([AttrTestController::class, 'thingEtc'], $action->target);

        $action = $router->find('ACTION', '/prefix1/kbtests/attr-test/thing-etc/456');
        $this->assertNotNull($action);
        $this->assertEquals([AttrTestController::class, 'thingEtc'], $action->target);

        $action = $router->find('ACTION', '/prefix2/kbtests/ns-test/test');
        $this->assertNotNull($action);
        $this->assertEquals([NsTestController::class, 'test'], $action->target);
    }


    public function testConvert()
    {
        $routes = [
            '/a/test/([0-9]+)' => 'regex number',
            '/another/([^/]+)' => 'regex segment',
            AttrTestController::class,
            NsTestController::class,
        ];

        $router = Router::create([
            'extract' => Router::EXTRACT_ALL | Router::EXTRACT_CONVERT_REGEX,
            'mode' => Router::MODE_REGEX,
        ]);

        $router->load($routes);

        $action = $router->find('GET', '/a/test/abc');
        $this->assertNull($action);

        $action = $router->find('GET', '/a/test/1234');
        $this->assertNotNull($action);
        $this->assertEquals('regex number', $action->target);

        $action = $router->find('POST', '/another/blah/blah/1234');
        $this->assertNull($action);

        $action = $router->find('POST', '/another/blah-blah-1234');
        $this->assertNotNull($action);
        $this->assertEquals('regex segment', $action->target);

        // Namespaces.
        $action = $router->find('ACTION', '/kbtests/ns-test/thing-etc/abc/123/bad');
        $this->assertNull($action);

        $action = $router->find('ACTION', '/kbtests/ns-test/thing-etc/abc/123');
        $this->assertNotNull($action);
        $this->assertEquals([NsTestController::class, 'thingEtc'], $action->target);

        $action = $router->find('ACTION', '/kbtests/variadic');
        $this->assertNull($action);

        $action = $router->find('ACTION', '/kbtests/bad-argument/abc');
        $this->assertNull($action);

        $action = $router->find('ACTION', '/kbtests/private-method');
        $this->assertNull($action);

        // Attributes.
        $action = $router->find('GET', '/thingo/123-abc/bad');
        $this->assertNull($action);

        $action = $router->find('GET', '/thingo/123-ok');
        $this->assertNotNull($action);
        $this->assertEquals([AttrTestController::class, 'thingEtc'], $action->target);

        // Ns from attribute controller.
        $action = $router->find('GET', '/kbtests/attr-test/thing-etc/blah');
        $this->assertNull($action);

        $action = $router->find('ACTION', '/kbtests/attr-test/thing-etc/blah');
        $this->assertNotNull($action);
        $this->assertEquals([AttrTestController::class, 'thingEtc'], $action->target);
    }
}

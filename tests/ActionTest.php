<?php

namespace kbtests;

use karmabunny\router\Router;
use kbtests\controllers\AttrTestController;
use kbtests\controllers\BaseController;
use PHPUnit\Framework\TestCase;

/**
 * TODO
 */
class ActionTest extends TestCase
{

    public function testCallable()
    {
        $router = Router::create();
        $router->load([
            '/abc' => [AttrTestController::class, 'actionTest'],
            '/builtin' => 'phpinfo',
            '/blah' => 'not-callable',
        ]);

        $action = $router->find('GET', '/abc');
        $this->assertNotNull($action);
        $this->assertTrue($action->isCallable());
        $this->assertTrue($action->isController(AttrTestController::class));
        $this->assertTrue($action->isController(BaseController::class));
        $this->assertFalse($action->isController(TestCase::class));

        $action = $router->find('GET', '/builtin');
        $this->assertNotNull($action);
        $this->assertTrue($action->isCallable());
        $this->assertFalse($action->isController(BaseController::class));

        $action = $router->find('GET', '/blah');
        $this->assertNotNull($action);
        $this->assertFalse($action->isCallable());
        $this->assertFalse($action->isController(BaseController::class));
    }


    // public function testInvoke()
    // {
    //     $router = Router::create();
    //     $router->load([
    //         '/abc' => [AttrTestController::class, 'actionTest'],
    //         '/builtin' => 'phpinfo',
    //         '/blah' => 'not-callable',
    //     ]);
    // }
}

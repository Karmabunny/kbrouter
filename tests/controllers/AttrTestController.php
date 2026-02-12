<?php

namespace kbtests\controllers;

use karmabunny\router\Route;


class AttrTestController extends BaseController
{

    /**
     * @route /thingo/{etc}
     * @route /duplicate/{etc}/123
     */
    public function thingEtc(string $etc) {}


    /**
     * @route /thingo/test
     */
    public function thingTest(string $etc) {}

    /**
     * @route GET /
     */
    public function actionRoot() {}

    /**
     * @route POST /thingo/{etc}
     */
    public function thingPost(string $etc) {}

    /**
     * @route GET /test
     */
    public function actionTest() {}

    /**
     * @route GET /thingo/{etc}
     */
    public function thingGet(string $etc) {}


    #[Route('/php8/*/only')]
    public function eightOnly() {}

    #[Route('/php8/another')]
    #[Route('/php8/repeated')]
    public function eightAnother() {}
}

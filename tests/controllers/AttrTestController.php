<?php

namespace kbtests\controllers;

use karmabunny\router\Route;


class AttrTestController
{
    /**
     * @route GET /test
     */
    public function actionTest() {}

    /**
     * @route /thingo/{etc}
     * @route /duplicate/{etc}/123
     */
    public function thingEtc(string $etc) {}

    #[Route('/php8/*/only')]
    public function eightOnly() {}

    #[Route('/php8/another')]
    #[Route('/php8/repeated')]
    public function eightAnother() {}
}

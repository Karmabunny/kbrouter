<?php

namespace kbtests\controllers;


class NsTestController extends BaseController
{
    public function test() {}

    public function thingEtc(string $etc, int $ooh) {}

    public function getXMLResult($input) {}

    public function big_long_method_name() {}

    public static function staticAction() {}

    public static function acceptableArgs(float $num, array $config = null, ?object $context) {}

    // Ignored
    public function _fakePrivate() {}

    // Ignored
    public function variadic(...$args) {}

    // Ignored
    public function badArgument(object $target) {}

    // Ignored
    protected function privateMethod() {}
}

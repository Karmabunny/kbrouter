<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\router;


/**
 *
 * @package karmabunny\router
 */
class Action
{

    public $method;

    public $path;

    public $target;

    public $rule;

    public $args;

    public function __construct($config)
    {
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }
}

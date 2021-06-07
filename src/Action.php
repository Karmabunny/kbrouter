<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\router;


/**
 * This represents an action-able route.
 *
 * After finding a match, this object is produced. It contains the source
 * elements (method, path, rule) and target elements (target, args).
 *
 * @package karmabunny\router
 */
class Action
{

    /** @var string */
    public $method;

    /** @var string */
    public $path;

    /** @var string */
    public $rule;

    /** @var mixed */
    public $target;

    /** @var string[] */
    public $args;


    /**
     * @param array $config
     */
    public function __construct($config)
    {
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }
}

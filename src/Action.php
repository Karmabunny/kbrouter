<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\router;

use ReflectionFunction;
use ReflectionMethod;

/**
 * This represents an action-able route.
 *
 * After finding a match, this object is produced. It contains the source
 * elements (method, path, rule) and target elements (target, args).
 *
 * The router and actions don't require the 'target' to be callable. Given that
 * 9/10 the targets are indeed callable, this class include a bunch of helpers
 * for that use-case.
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


    /**
     * Is the 'target' callable?
     *
     * However, this doesn't mean that one can invoke the property directly.
     * Use the `invoke()` helper and provide an 'instance' object.
     *
     * @return bool
     */
    public function isCallable(): bool
    {
        return Router::isCallable($this->target);
    }


    /**
     * The target is for a matching controller.
     *
     * @param object|string $controller
     * @return bool
     */
    public function isController($controller): bool
    {
        if (!is_array($this->target)) return false;
        if (count($this->target) != 2) return false;

        [$class, $method] = $this->target;

        return (
            $class === $controller or
            is_subclass_of($class, $controller)
        );
    }


    /**
     * Call the target.
     *
     * This doesn't execute the `isCallable()` check.
     * You must do this before calling this method.
     *
     * Optionally provide an 'instance' of a 'controller' class to call methods.
     * Again, you must call the `isController()` check.
     *
     * @param object|null $instance
     * @return mixed
     */
    public function invoke($instance = null)
    {
        // PHP 8 can expand keyed arrays. It's truly magical.
        if (PHP_VERSION_ID >= 80000) {
            if ($instance) {
                [$class, $name] = $this->target;
                return $instance->$name(...$this->args);
            }
            else {
                return ($this->target)(...$this->args);
            }
        }
        // Otherwise we've got to reflect everything.
        else {
            if ($instance) {
                [$class, $name] = $this->target;
                $reflect = new ReflectionMethod($instance, $name);
                $function = [$instance, $name];
            }
            else {
                $reflect = new ReflectionFunction($this->target);
                $function = $this->target;
            }

            // Reshuffle named args into the correct order.
            $args = $this->args;
            $params = [];

            foreach ($reflect->getParameters() as $param) {
                $name = $param->getName();
                $arg = $this->args[$name] ?? null;
                if ($arg === null) continue;

                $params[] = $arg;
                unset($args[$name]);
            }

            // Include wildcards at the end.
            foreach ($args as $arg) {
                $params[] = $arg;
            }

            return $function(...$params);
        }
    }
}

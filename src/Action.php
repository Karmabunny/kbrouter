<?php
declare(strict_types=1);
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\router;

use ReflectionClass;
use ReflectionException;
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

    /**
     * The HTTP verb, like GET/POST/etc.
     *
     * @var string
     */
    public string $method;

    /**
     * The Request URI.
     *
     * @var string
     */
    public string $path;

    /**
     * The rule pattern that matched.
     *
     * @var string
     */
    public string $rule;

    /**
     * The route target.
     *
     * Could be any, but typically a callable like `[class, method]`.
     *
     * @var mixed
     */
    public mixed $target;

    /**
     * The path arguments parsed from the path + rule.
     *
     * @var string[]
     */
    public array $args;


    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
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
     * @param object|class-string $controller
     * @return bool
     */
    public function isController(object|string $controller): bool
    {
        if (!is_array($this->target)) return false;
        if (count($this->target) != 2) return false;

        [$class, $method] = $this->target;

        try {
            $reflect = new ReflectionClass($class);

            if (
                $reflect->isAbstract()
                or $reflect->isInterface()
                or $reflect->isTrait()
            ) {
                return false;
            }

            if (
                $reflect->getName() !== $controller
                and !$reflect->isSubclassOf($controller)
            ) {
                return false;
            }

            return true;
        }
        catch (ReflectionException $error) {
            return false;
        }
    }


    /**
     * Create the controller instance.
     *
     * This doesn't assert the controller type, first use {@see isController()}.
     *
     * @param array $args constructor arguments
     * @return object|null
     */
    public function createController(array $args): ?object
    {
        if (!is_array($this->target)) return null;
        if (count($this->target) != 2) return null;

        [$class, $method] = $this->target;
        return new $class(...$args);
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
    public function invoke(?object $instance = null): mixed
    {
        // PHP 8 can expand keyed arrays. It's truly magical.
        if ($instance) {
            [$class, $name] = $this->target;
            return $instance->$name(...$this->args);
        }
        else {
            return ($this->target)(...$this->args);
        }
    }
}

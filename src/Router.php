<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\router;

use InvalidArgumentException;
use karmabunny\router\Modes\RouterChunkedMode;
use karmabunny\router\Modes\RouterRegexMode;
use karmabunny\router\Modes\RouterSingleMode;

/**
 * A regex powered router.
 *
 * @package karmabunny\router
 */
abstract class Router
{

    const MODE_CHUNKED = 'chunked';
    const MODE_SINGLE = 'single';
    const MODE_REGEX = 'regex';

    const MODES = [
        self::MODE_SINGLE => RouterSingleMode::class,
        self::MODE_CHUNKED => RouterChunkedMode::class,
        self::MODE_REGEX => RouterRegexMode::class,
    ];

    /**
     * Match for rule variables like `{var}`, after `preg_quote`.
     *
     * @var string
     */
    const RULE_TEMPLATE = '!\\\{([a-z][a-z0-9_]*)\\\}!i';


    /** @var array rule => target */
    public $routes = [];

    /** @var RouterConfig */
    public $config;


    /**
     * @param array $routes
     */
    protected function __construct($config)
    {
        if (is_array($config)) {
            $config = new RouterConfig($config);
        }

        $this->config = $config;
    }


    /**
     * Create a router.
     *
     * @param RouterConfig $config
     * @return Router
     * @throws InvalidArgumentException
     */
    public static function create($config = []): Router
    {
        if (is_array($config)) {
            $config = new RouterConfig($config);
        }

        $class = self::MODES[$config->mode] ?? null;

        if (!$class) {
            throw new InvalidArgumentException('Unknown router mode: ' . $config->mode);
        }

        return new $class($config);
    }


    /**
     * Load routes.
     *
     * @param array $routes [ rule => target ]
     * @return void
     */
    public function load(array $routes)
    {
        $this->routes = $routes;
    }


    /**
     * Find a matching route.
     *
     * @param string $method
     * @param string $path
     * @return null|Action
     */
    public abstract function find(string $method, string $path): ?Action;


    /**
     * Determine the parameter names of a route pattern.
     *
     * @param string $rule Route pattern
     * @return string[]
     */
    public static function extractRuleNames(string $rule): array
    {
        $rule = preg_quote($rule, '/');

        $matches = [];
        if (!preg_match_all(self::RULE_TEMPLATE, $rule, $matches, PREG_PATTERN_ORDER)) {
            return [];
        }
        return $matches[1];
    }


    /**
     * Insert parameters into a route pattern.
     *
     * @param string $rule Route pattern
     * @param array $parameters Keyed array: name => value
     * @return string A regular string path
     */
    public static function fillRuleValues(string $rule, array $parameters): string
    {
        $rule = preg_quote($rule, '!');

        return preg_replace_callback(
            self::RULE_TEMPLATE,
            function($m) use ($parameters) {
                // Arg name or fallback to the template.
                return $parameters[$m[1]] ?? $m[0];
            },
            $rule
        );
    }


    /**
     * Given an array of matches from `preg_match()` the named groups are
     * duplicate entries. This will strip the numeric versions _and_ append
     * non-named groups to the end of the array.
     *
     * ```
     * // Where 'a' + 'b' are names groups, 'm2' is numeric
     * $matches = [
     *    'a' => 'm1',
     *    'm1',
     *    'm2',
     *    'b' => 'm3',
     *    'm3',
     * ];
     * // Normalise
     * $args = [
     *    'a' => 'm1',
     *    'b' => 'm3',
     *    'm2',
     * ];
     * ```
     *
     * **Beware!** The process requires the wildcard values to be unique from
     * the named groups. Currently, I'm sure there's a way to fix this.
     *
     * @param array $matches
     * @return array
     */
    public static function normalizeMatches(array $matches): array
    {
        $args = [];
        foreach ($matches as $key => $arg) {
            if (is_numeric($key)) continue;
            $args[$key] = $arg;
        }

        // $wildcards = [];
        // foreach ($matches as $value) {
        //     $count = 0;

        //     foreach ($args as $arg) {
        //         if ($arg != $value) continue;
        //         $count++;
        //     }

        //     if ($count < 2) {
        //         $wildcards[] = $value;
        //     }
        // }

        $wildcards = array_diff($matches, $args);

        foreach ($wildcards as $arg) {
            $args[] = $arg;
        }

        return $args;
    }
}

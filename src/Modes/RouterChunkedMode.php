<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\router\Modes;

use karmabunny\router\Action;
use karmabunny\router\Router;

/**
 *
 * @package karmabunny\router
 */
class RouterChunkedMode extends Router
{

    const PATTERN_POSITION = '([^/]+?)';


    /** @var array [ pattern, names, indexes ] */
    public $patterns = [];


    /** @inheritdoc */
    public function find(string $method, string $path): ?Action
    {
        foreach ($this->patterns as [$pattern, $names, $indexes]) {
            if (!preg_match($pattern, "{$method} {$path}", $matches)) continue;

            foreach ($indexes as $index => [$rule, $target]) {
                $found = $matches[$index] ?? null;
                if ($found) break;
                unset($found);
            }

            if (!$found) return null;

            $args = [];

            foreach ($matches as $i => $arg) {
                if (!$arg) continue;
                if ($arg == $found) continue;

                $name = $names[$i - 1] ?? null;
                if (!$name) continue;

                $args[$name] = $arg;
            }

            return new Action([
                'target' => $target,
                'args' => $args,
                'rule' => $rule,
                'method' => $method,
                'path' => $path,
            ]);
        }

        return null;
    }


    /** @inheritdoc */
    public function load(array $routes)
    {
        parent::load($routes);
        $this->compile();
    }


    /**
     *
     * @return void
     */
    public function compile()
    {
        $rules = array_chunk($this->routes, 10, true);
        $methods = implode('|', $this->config->methods);

        foreach ($rules as $chunk) {
            $names = [];
            $patterns = [];
            $indexes = [];

            foreach ($chunk as $rule => $target) {
                $names[] = null; // route.
                $indexes[count($names)] = [$rule, $target];

                $pattern = preg_replace_callback(
                    self::RULE_TEMPLATE,
                        function($matches) use (&$names) {
                    $names[] = $matches[1];
                    return self::PATTERN_POSITION;
                }, $rule);

                if (!preg_match("!^(?:{$methods})\s+!", $rule)) {
                    $pattern = "[^\s]+ " . $pattern;
                }

                $patterns[] = "({$pattern})";
            }

            $pattern = '!^(?:' . implode('|', $patterns) . ')$!';

            if ($this->config->case_insensitive) {
                $pattern .= 'i';
            }

            $this->patterns[] = [
                $pattern,
                $names,
                $indexes,
            ];
        }
    }


    // /**
    //  * Expand route patterns into full regex patterns.
    //  *
    //  * Route patterns are like this:
    //  * => /path/{one}/to/{two}/something
    //  *
    //  * These {} brackets are applied to the controller methods as named arguments.
    //  * This function will create a regex capable pattern to extract these.
    //  *
    //  * @param string $rule Route pattern
    //  * @return string Regex pattern
    //  */
    // public function expandRuleSet(string $rule)
    // {
    //     $pattern = '!^(?:' . implode('|', $this->methods) . ')\s+';

    //     $matches = [];
    //     if (preg_match($pattern . '!', $rule, $matches)) {
    //         $pattern = $matches[1];
    //     }

    //     $names = [];
    //     $pattern .= preg_replace_callback(self::RULE_TEMPLATE,
    //             function ($matches) use (&$names) {
    //         $names[] = $matches[1];
    //         return self::PATTERN_POSITION;
    //     }, $rule);

    //     $pattern .= '$!';

    //     return [
    //         $pattern,
    //         $names,
    //     ];
    // }
}

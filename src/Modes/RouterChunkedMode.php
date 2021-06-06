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

    /**
     * Regex {var} or wildcard.
     *
     * - Match one, contains a var name
     * - Empty, it's a wildcard
     *
     * @var string
     */
    const PATTERN_RULE = '!(?:\\\{([a-z][a-z0-9_]*)\\\}|\\\\\*+)!i';

    const PATTERN_POSITION = '([^/]+?)';

    const PATTERN_WILD = '(.+?)';


    /** @var array [ pattern, rules ] */
    public $patterns = [];


    /** @inheritdoc */
    public function find(string $method, string $path): ?Action
    {
        foreach ($this->patterns as [$pattern, $rules]) {
            if (!preg_match($pattern, "{$method} {$path}", $matches)) continue;

            foreach ($rules as $index => [$rule, $target, $names]) {
                $found = $matches[$index] ?? null;
                if ($found) break;
                $found = null;
            }

            if (!$found) return null;

            $matches = array_slice($matches, $index + 1, count($names));

            $args = [];
            $wildcards = [];

            foreach ($matches as $i => $arg) {
                if (!$arg) continue;

                $name = $names[$i] ?? null;

                if ($name !== null) {
                    $args[$name] = $arg;
                }
                else {
                    $wildcards[] = $arg;
                }
            }

            foreach ($wildcards as $arg) {
                $args[] = $arg;
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
        $routes = array_chunk($this->routes, 10, true);
        $methods = implode('|', $this->config->methods);

        foreach ($routes as $chunk) {
            $index = 0;
            $patterns = [];
            $rules = [];

            foreach ($chunk as $rule => $target) {
                $names = [];

                $pattern = preg_quote($rule, '!');

                $pattern = preg_replace_callback(
                    self::PATTERN_RULE,
                    function ($matches) use (&$names) {
                        $name = $matches[1] ?? null;
                        $names[] = $name;

                        return $name === null
                            ? self::PATTERN_WILD
                            : self::PATTERN_POSITION;
                    },
                    $pattern
                );

                if (!preg_match("!^(?:{$methods})\s+!", $rule)) {
                    $pattern = "[^\s]+ " . $pattern;
                }

                $rules[++$index] = [
                    $rule,
                    $target,
                    $names,
                ];

                $index += count($names);
                $patterns[] = "({$pattern})";
            }

            $pattern = '!^(?:' . implode('|', $patterns) . ')$!';

            if ($this->config->case_insensitive) {
                $pattern .= 'i';
            }

            $this->patterns[] = [
                $pattern,
                $rules,
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

<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\router\Modes;

use karmabunny\router\Action;
use karmabunny\router\Router;

/**
 * Chunked mode will match routes with a big chunked regex.
 *
 * This is largely inspired by FastRoute.
 * https://www.npopov.com/2014/02/18/Fast-request-routing-using-regular-expressions.html
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

    /** @var string */
    const PATTERN_POSITION = '([^/]+?)';

    /** @var string */
    const PATTERN_WILD = '(.+?)';


    /** @var array [ pattern, rules ] */
    public $patterns = [];


    /** @inheritdoc */
    public function find(string $method, string $path): ?Action
    {
        foreach ($this->patterns as [$pattern, $rules]) {
            if (!preg_match($pattern, "{$method} {$path}", $matches)) continue;

            // To find the matching route, the rules are index by the route's
            // capture group. The route data (rule, target, names) is
            // intentionally bled from this loop.
            foreach ($rules as $index => [$rule, $target, $names]) {
                $found = $matches[$index] ?? null;
                if ($found) break;
                $found = null;
            }

            // This shouldn't happen.
            if (!$found) return null;

            // A subset of matches - these are the arguments.
            $matches = array_slice($matches, $index + 1, count($names));

            $args = [];
            $wildcards = [];

            // Of the matches, determine their name + type.
            // Named variables have names, otherwise wildcards.
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

            // Wildcards always come second.
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
     * Creates chunked regex patterns and related metadata.
     *
     * If you're following the FastRoute blog, this creates patterns with
     * 'Group Position Based Chunked' method.
     *
     * I couldn't grok how to create 'Group Count Based' and it's complexity
     * didn't feel beneficial. I may eat my words later. We can always switch
     * that in later if we need to.
     *
     * @return void
     */
    public function compile()
    {
        $routes = array_chunk($this->routes, 10, true);
        $methods = implode('|', $this->config->methods);

        // We're going to build a set of hefty patterns.
        // The 'rules' is a map of [ indexes => route-data ].
        foreach ($routes as $chunk) {
            // Tracking capture groups.
            $index = 0;

            // Collection of shards for this chunk pattern.
            $patterns = [];

            // Route data, group index => [ rule, target, names ].
            $rules = [];

            // For each rule, build a pattern shard and the route data.
            // Route data is stored against the matched index.
            foreach ($chunk as $rule => $target) {
                $names = [];

                $pattern = preg_quote($rule, '!');

                // A positive match is a variable name.
                // No match means wildcard.
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

                // Tack on a ANY method if not present.
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

            // Mush all the patterns together.
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

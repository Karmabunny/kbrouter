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
class RouterSingleMode extends Router
{

    const RULE_WILDCARD = '!\\\\\*+!';

    const PATTERN_NAMED = '(?<\1>[^/]+?)';

    const PATTERN_WILD = '(.+?)';


    /** @inheritdoc */
    public function find(string $method, string $path): ?Action
    {
        foreach ($this->routes as $rule => $target) {
            $pattern = $this->expandRule($rule);

            $matches = [];
            if (!preg_match($pattern, "{$method} {$path}", $matches)) continue;

            array_shift($matches);
            $args = self::normalizeMatches($matches);

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


    /**
     * Expand route patterns into full regex patterns.
     *
     * Route patterns are like this:
     * => /path/{one}/to/{two}/something
     *
     * These {} brackets are applied to the controller methods as named arguments.
     * This function will create a regex capable pattern to extract these.
     *
     * @param string $rule Route pattern
     * @return string Regex pattern
     */
    public function expandRule(string $rule): string
    {
        $pattern = preg_quote($rule, '!');
        $pattern = preg_replace(
            [self::RULE_TEMPLATE, self::RULE_WILDCARD],
            [self::PATTERN_NAMED, self::PATTERN_WILD],
            $pattern
        );

        $methods = implode('|', $this->config->methods);

        if (!preg_match("!^(?:{$methods})\s+!", $pattern)) {
            $pattern = "[^\s]+ " . $pattern;
        }

        $pattern = "!^{$pattern}$!";

        if ($this->config->case_insensitive) {
            $pattern .= 'i';
        }

        return $pattern;
    }

}

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

    const PATTERN_NAMED = '(?<\1>[^/]+?)';

    public $patterns = [];


    /** @inheritdoc */
    public function find(string $method, string $path): ?Action
    {
        foreach ($this->routes as $rule => $target) {
            $pattern = $this->expandRule($rule);

            $matches = [];
            if (!preg_match($pattern, "{$method} {$path}", $matches)) continue;

            $args = [];

            foreach ($matches as $key => $arg) {
                if (is_numeric($key)) continue;
                $args[$key] = $arg;
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
        $pattern = preg_replace(
            self::RULE_TEMPLATE,
            self::PATTERN_NAMED,
            $rule
        );

        $methods = implode('|', $this->config->methods);

        if (!preg_match("!^(?:{$methods})\s+!", $rule)) {
            $pattern = "[^\s]+ " . $pattern;
        }

        $pattern = "!^{$pattern}$!";

        if ($this->config->case_insensitive) {
            $pattern .= 'i';
        }

        return $pattern;
    }

}

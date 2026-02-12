<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\router\Modes;

use karmabunny\router\Action;
use karmabunny\router\Router;

/**
 * A regex-style router.
 *
 * This is the least preferred - it's difficult to optimise and encourages bad
 * route design.
 *
 * It exists as a transitional implementation.
 *
 * @package karmabunny\router
 */
class RouterRegexMode extends Router
{

    /** @inheritdoc */
    public function find(string $method, string $path): ?Action
    {
        $method = strtoupper($method);

        foreach ($this->routes as $rule => $target) {
            $pattern = $this->expandRule($rule);

            $matches = [];
            if (!preg_match($pattern, "{$method} {$path}", $matches)) continue;

            // Collect the arguments.
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
     * Build a route pattern.
     *
     * This is an internal detail to add the method component + flags
     * to a pattern.
     *
     * @param string $pattern Route pattern
     * @return string Regex pattern
     */
    protected function expandRule(string $pattern): string
    {
        $methods = implode('|', $this->config->methods);

        // Add a method if doesn't already exist.
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

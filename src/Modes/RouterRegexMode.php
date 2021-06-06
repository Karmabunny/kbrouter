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
class RouterRegexMode extends Router
{

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
     *
     *
     * @param string $pattern Route pattern
     * @return string Regex pattern
     */
    public function expandRule(string $pattern): string
    {
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

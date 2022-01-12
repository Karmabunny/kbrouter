<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\router\Modes;

use karmabunny\router\Action;
use karmabunny\router\Router;

/**
 * Single mode router will match routes one-by-one.
 *
 * It's not optimised - but is brutally simple so it unlikely to break in
 * wonderful ways.
 *
 * @package karmabunny\router
 */
class RouterSingleMode extends Router
{

    /**
     * Match for rule wildcards like `*`, after `preg_quote`.
     *
     * @var string
     */
    const RULE_WILDCARD = '!\\\\\*+!';


    /**
     * Replacement for rule variables.
     *
     * The backref accepts the variable name extracted by RULE_TEMPLATE.
     *
     * @var string
     */
    const PATTERN_NAMED = '(?<\1>[^/]+?)';


    /** @var string */
    const PATTERN_WILD = '(.+?)';


    /** @var array [ pattern => [rule, target] ] */
    public $patterns = [];


    /** @inheritdoc */
    public function find(string $method, string $path): ?Action
    {
        foreach ($this->patterns as $pattern => [$rule, $target]) {
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

        // No match - 404.
        return null;
    }


    /** @inheritdoc */
    public function load(array $routes): array
    {
        $routes = parent::load($routes);
        if ($routes) {
            $this->compile($routes);
        }
        return $routes;
    }


    /**
     * Create regex patterns from the route rules.
     *
     * @param array $routes
     * @return void
     */
    public function compile(array $routes)
    {
        foreach ($routes as $rule => $target) {
            $pattern = $this->expandRule($rule);
            $this->patterns[$pattern] = [$rule, $target];
        }
    }


    /**
     * Expand route patterns into full regex patterns.
     *
     * Route patterns are like this:
     * => /path/{one}/to/{two}/something
     *
     * Variable templates `{var}` are converted to `'[^/]+?'`.
     *
     * Wildcards `*` are converted to `'.+?'`.
     *
     * @param string $rule
     * @return string
     */
    protected function expandRule(string $rule): string
    {
        $pattern = self::convertRuleToPattern($rule);

        // If no existing method, add an ANY match.
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


    /**
     * Convert rule patterns to a regex. This only performs the argument swaps.
     * Any methods, flags, etc - are handled elsewhere.
     *
     * @param string $rule Route pattern
     * @return string Regex pattern
     */
    public static function convertRuleToPattern(string $rule): string
    {
        // Escape and swap out in the patterns.
        $pattern = preg_quote($rule, '!');
        $pattern = preg_replace(
            [self::RULE_TEMPLATE, self::RULE_WILDCARD],
            [self::PATTERN_NAMED, self::PATTERN_WILD],
            $pattern
        );

        return $pattern;
    }
}

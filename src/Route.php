<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\router;

use Attribute;

/**
 * A route is an attribute or @route tag that can be 'discovered' by the
 * {@see Router::loadFrom} method.s
 *
 * These enable one to declare their routes beside the action handlers.
 *
 * @package karmabunny\router
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{

    /** @var string */
    public $rule;


    /**
     * Create a route.
     *
     * The attached method is the callable 'target'.
     *
     * @param string $rule
     */
    public function __construct(string $rule)
    {
        $this->rule = $rule;
    }


    /**
     * Create a set of routes from a doc comment.
     *
     * This parses a '@route' tag.
     *
     * @param string $doc
     * @return Route[]
     */
    public static function parseDoc(string $doc): array
    {
        $matches = [];
        if (!preg_match_all('/@route ([^\n]+)/', $doc, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $routes = [];
        foreach ($matches as $match) {
            [$_, $rule] = $match;
            $routes[] = new Route(trim($rule));
        }

        return $routes;
    }

}

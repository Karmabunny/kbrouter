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
use PHP_CodeSniffer\Reports\Csv;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

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


    static $STRIP_ACTION_PATHS = [
        '/app',
        '/core',
        '/module',
        '/bloom',
        '/controllers',
        '-controller',
    ];


    /** @var array rule => target */
    public $routes = [];

    /** @var RouterConfig */
    public $config;


    /**
     * @param RouterConfig|array $config
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
     * @param RouterConfig|array $config
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
        $this->routes = array_merge($this->routes, $routes);
    }


    /**
     * Load magic routes from a class.
     *
     * @param string|object $class
     * @return void
     */
    public function loadFrom($class)
    {
        $this->loadAttributes($class);
        $this->loadNamespaces($class);
    }


    /**
     * Load attribute routes from a class.
     *
     * @param string|object $class
     * @return void
     */
    public function loadAttributes($class)
    {
        $this->load(self::extractFromAttributes($class));
    }


    /**
     *
     *
     * @param string|object $class
     * @return void
     */
    public function loadNamespaces($class)
    {
        $this->load(self::extractFromNamespaces($class));
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

        if (!preg_match_all(
            self::RULE_TEMPLATE,
            $rule,
            $matches,
            PREG_PATTERN_ORDER
        )) return [];

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
     * @param array $matches
     * @return array
     */
    public static function normalizeMatches(array $matches): array
    {
        $args = [];
        $wildcards = [];
        $ignore = false;

        // Named groups are always followed by an unnamed group.
        foreach ($matches as $key => $arg) {
            if (!is_numeric($key)) {
                $args[$key] = $arg;
                $ignore = true;
                continue;
            }

            if (!$ignore) {
                $wildcards[] = $arg;
            }

            $ignore = false;
        }

        // Tack on the wildcards at the end.
        foreach ($wildcards as $arg) {
            $args[] = $arg;
        }

        return $args;
    }


    /**
     * Find all the attribute + namespace routes on a target object/class.
     *
     * @param string|object $class
     * @return string[] [ rule => target ]
     * @throws ReflectionException
     */
    public static function extractRoutes($class): array
    {
        $routes = [];

        foreach (self::extractFromAttributes($class) as $rule => $target) {
            $routes[$rule] = $target;
        }

        foreach (self::extractFromNamespaces($class) as $rule => $target) {
            $routes[$rule] = $target;
        }

        return $routes;
    }


    /**
     * Find all the attribute routes on a target object/class.
     *
     * An attribute route is a PHP8 attribute or a @route doc comment.
     *
     * @param string|object $class
     * @return array [ rule => target ]
     * @throws ReflectionException
     */
    public static function extractFromAttributes($class): array
    {
        $reflect = new ReflectionClass($class);
        $methods = $reflect->getMethods(ReflectionMethod::IS_PUBLIC);

        $routes = [];

        // Searching through all the public methods.
        foreach ($methods as $method) {
            $target = [$class, $method->getShortName()];

            // God-master race PHP8.
            if (PHP_VERSION_ID > 80000) {
                // @phpstan-ignore-next-line : PHP8 property, already guarded.
                $attributes = $method->getAttributes(Route::class);

                foreach ($attributes as $attribute) {
                    /** @var Route $route */
                    $route = $attribute->newInstance();
                    $routes[$route->rule] = $target;
                }
            }

            // Just the peasant version for everyone else.
            $docs = Route::parseDoc($method->getDocComment() ?: '');

            foreach ($docs as $doc) {
                $routes[$doc->rule] = $target;
            }
        }

        return $routes;
    }


    /**
     * Find all the namespace routes on a target object/class.
     *
     * A namespace route is one generated from the class namespace + method + arguments.
     *
     * Like so:
     * ```
     * ## /Bloom/Controllers/ToolsController::dbSync($schema)
     * => 'ACTION /tools/db-sync/{$schema}'
     * ```
     *
     * Note the verb is 'ACTION'. It's up to you what an 'action' is.
     *
     * If you want to match everything I guess something like this works:
     * ```
     * $route = $router->find($method, $path);
     * if (!$route) {
     *    $route = $router->find('ACTION', $path);
     * }
     * ```
     *
     * Special keywords are ignored by the `STRIP_ACTION_PATHS` static config.
     *
     * By default:
     * - '/controllers'
     * - '-controller'
     *
     * What qualifies as an acceptable action method:
     * - must be public
     * - cannot be variadic
     * - cannot be a constructor/destructor
     * - cannot start with an underscore
     * - arguments must be one of: string, int, float, mixed
     *
     * @param string|object $class
     * @return array [ rule => target ]
     * @throws ReflectionException
     */
    public static function extractFromNamespaces($class): array
    {
        $reflect = new ReflectionClass($class);
        $methods = $reflect->getMethods(ReflectionMethod::IS_PUBLIC);

        $routes = [];

        foreach ($methods as $method) {
            $name = $method->getShortName();

            // Some basic filters.
            if (strpos($name, '_') === 0) continue;
            if ($method->isVariadic()) continue;
            if (!$method->isUserDefined()) continue;
            if ($method->isConstructor()) continue;
            if ($method->isDestructor()) continue;

            $args = [];

            // Find some args first.
            foreach ($method->getParameters() as $parameter) {
                $type = $parameter->getType();
                $type_names = [];

                if ($type instanceof ReflectionNamedType) {
                    $type_names[] = $type->getName();
                }
                else if ($type instanceof ReflectionUnionType) {
                    foreach ($type->getTypes() as $sub_type) {
                        // @phpstan-ignore-next-line: This IS ALWAYS a named type. Gah.
                        $type_name[] = $sub_type->getName();
                    }
                }
                else {
                    $type_names[] = 'mixed';
                }

                // Because route segments are just strings, we can only jam in these.
                // This assumes a non-strict type mode.
                $types = array_intersect($type_names, ['string', 'int', 'float', 'mixed']);

                // Not a supported arg type so skip the whole method.
                if (empty($types)) {
                    // But if the arg is nullable we can just skip over it.
                    if ($type and $type->allowsNull()) continue;

                    // No good, skip the whole method.
                    continue 2;
                }

                $args[] = '{' . $parameter->getName() . '}';
            }

            // Target + initial rule.
            $target = [$class, $name];
            $rule = str_replace('\\', '/', implode('/', $target));

            // Convert camel case to kebab case.
            $rule = preg_replace_callback(
                '/[A-Z0-9]/',
                function ($matches) {
                    return '-' . strtolower($matches[0]);
                },
                $rule
            );

            // Clean out weird artifacts + some common stuff.
            $rule = str_replace('/-', '/', $rule);
            $rule = str_replace('_', '-', $rule);
            $rule = str_replace(static::$STRIP_ACTION_PATHS, '', $rule);

            // Tack on the arguments.
            if ($args) {
                $rule .= '/' . implode('/', $args);
            }

            // Nice.
            $routes["ACTION /{$rule}"] = $target;
        }

        return $routes;
    }


    /**
     * Add routes discovered on the target class/object to a route config.
     *
     * This finds both attribute + namespace routes.
     *
     * Use this to build a route table and then `load()` it into a router.
     * Or instead load the class routes directly into the router with `loadFrom()`.
     *
     * @param array $routes [ rule => target ]
     * @param string|object $class
     * @return void
     * @throws ReflectionException
     */
    public static function addAutoRoutes(array &$routes, $class)
    {
        self::addFromAttributes($routes, $class);
        self::addFromNamespaces($routes, $class);
    }


    /**
     * Add attribute routes from the target class/object.
     *
     * Use this to build a route table and then `load()` it into a router.
     * Or instead load the class routes directly into the router with `loadFrom()`.
     *
     * @param array $routes [ rule => target ]
     * @param string|object $class
     * @return void
     * @throws ReflectionException
     */
    public static function addFromAttributes(array &$routes, $class)
    {
        $auto = self::extractFromAttributes($class);

        foreach ($auto as $rule => $target) {
            $routes[$rule] = $target;
        }
    }


    /**
     * Add namespace routes from the target class/object.
     *
     * Use this to build a route table and then `load()` it into a router.
     * Or instead load the class routes directly into the router with `loadFrom()`.
     *
     * @param array $routes [ rule => target ]
     * @param string|object $class
     * @return void
     * @throws ReflectionException
     */
    public static function addFromNamespaces(array &$routes, $class)
    {
        $auto = self::extractFromNamespaces($class);

        foreach ($auto as $rule => $target) {
            $routes[$rule] = $target;
        }
    }
}

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
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * A regex powered router.
 *
 * @package karmabunny\router
 */
abstract class Router
{

    /** Some standard default methods. */
    const METHODS = [
        'HEAD',
        'GET',
        'OPTIONS',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
    ];

    const MODE_CHUNKED = 'chunked';
    const MODE_SINGLE = 'single';
    const MODE_REGEX = 'regex';

    const MODES = [
        self::MODE_SINGLE => RouterSingleMode::class,
        self::MODE_CHUNKED => RouterChunkedMode::class,
        self::MODE_REGEX => RouterRegexMode::class,
    ];

    const EXTRACT_NONE = 0;
    const EXTRACT_NAMESPACES = 1;
    const EXTRACT_ATTRIBUTES = 2;
    const EXTRACT_ALL = 3;
    const EXTRACT_CONVERT_REGEX = 128;
    const EXTRACT_WITH_PREFIXES = 256;

    /**
     * Match for rule variables like `{var}`, after `preg_quote`.
     *
     * @var string
     */
    const RULE_TEMPLATE = '!\\\{([a-z][a-z0-9_]*)\\\}!i';


    /**
     * Things to remove from namespace generated 'action' routes.
     *
     * Ah regrets, this should have been a config. But that would mean that
     * extractFromNamespace() is non-static and it just gets messy from there on.
     *
     * @var string[]
     */
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
     * @return array [ rule => target ] diff of new routes
     */
    public function load(array $routes)
    {
        $new_routes = [];
        $class_routes = [];

        // If enabled, the target can be a class. From this we extract
        // routes via attributes and/or namespaces.
        foreach ($routes as $rule => $target) {

            // Duplicate route, skip it.
            if (array_key_exists($rule, $this->routes)) {
                continue;
            }

            // Parse routes from objects + class strings.
            if (
                ($this->config->extract & self::EXTRACT_ALL)
                and (is_string($target) or is_object($target))
                and class_exists($target)
                and (
                    ($this->config->extract & self::EXTRACT_WITH_PREFIXES)
                    or is_numeric($rule)
                )
            ) {
                $prefix = '';

                if (is_string($rule)) {
                    $prefix = $rule;
                }

                if ($this->config->extract & self::EXTRACT_NAMESPACES) {
                    self::addFromNamespaces($class_routes, $target, $prefix);
                }

                if ($this->config->extract & self::EXTRACT_ATTRIBUTES) {
                    self::addFromAttributes($class_routes, $target, $prefix);
                }

                continue;
            }

            // Skip invalid numeric rules.
            // TODO or throw an error..?
            if (is_numeric($rule)) {
                continue;
            }

            $new_routes[$rule] = $target;
        }

        // Convert class rules into patterns.
        if ($this->config->extract & self::EXTRACT_CONVERT_REGEX) {
            foreach ($class_routes as $rule => $target) {
                $pattern = RouterSingleMode::convertRuleToPattern($rule);
                $new_routes[$pattern] = $target;
            }
        }
        // Or not.
        else {
            $new_routes = array_merge($new_routes, $class_routes);
        }

        $this->routes = array_merge($this->routes, $new_routes);
        return $new_routes;
    }


    /**
     * Load magic routes from a class.
     *
     * @param string|object $class
     * @return void
     */
    public function loadFrom($class, $prefix = '')
    {
        $this->loadAttributes($class, $prefix);
        $this->loadNamespaces($class, $prefix);
    }


    /**
     * Load attribute routes from a class.
     *
     * @param string|object $class
     * @return void
     */
    public function loadAttributes($class, $prefix = '')
    {
        $this->load(self::extractFromAttributes($class));
    }


    /**
     *
     *
     * @param string|object $class
     * @return void
     */
    public function loadNamespaces($class, $prefix = '')
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
     * @param string $prefix
     * @return array [ rule => target ]
     * @throws ReflectionException
     */
    public static function extractFromAttributes($class, $prefix = ''): array
    {
        $reflect = new ReflectionClass($class);
        $methods = $reflect->getMethods(ReflectionMethod::IS_PUBLIC);

        if ($prefix) {
            $prefix = rtrim($prefix, '/') . '/';
        }

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
                    $rule = explode(' ', $route->rule, 2);

                    if (count($rule) == 2) {
                        $rule[1] = $prefix . $rule[1];
                    }
                    else {
                        $rule[0] = $prefix . $rule[0];
                    }

                    // Tidy up.
                    $rule = preg_replace('|/+|', '/', implode(' ', $rule));
                    $rule = rtrim($rule, '/');

                    $routes[$rule] = $target;
                }
            }

            // Just the peasant version for everyone else.
            $docs = Route::parseDoc($method->getDocComment() ?: '');

            foreach ($docs as $doc) {
                // Insert the prefix before the method.
                // Takes a bit of work.
                $rule = explode(' ', $doc->rule, 2);

                if (count($rule) == 2) {
                    $rule[1] = $prefix . $rule[1];
                }
                else {
                    $rule[0] = $prefix . $rule[0];
                }

                // Tidy up.
                $rule = preg_replace('|/+|', '/', implode(' ', $rule));
                $rule = rtrim($rule, '/');

                $routes[$rule] = $target;
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
     * @param string $prefix
     * @return array [ rule => target ]
     * @throws ReflectionException
     */
    public static function extractFromNamespaces($class, $prefix = ''): array
    {
        $reflect = new ReflectionClass($class);
        $methods = $reflect->getMethods(ReflectionMethod::IS_PUBLIC);

        $routes = [];

        if ($prefix) {
            $prefix = rtrim($prefix, '/') . '/';
        }

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

            $rule = '/' . $rule;

            // Clean out weird artifacts + some common stuff.
            $rule = str_replace('/-', '/', $rule);
            $rule = str_replace('_', '-', $rule);
            $rule = str_replace(static::$STRIP_ACTION_PATHS, '', $rule);

            // Add the prefix.
            $rule = trim($prefix . $rule, '/');

            // Tack on the arguments.
            if ($args) {
                $rule .= '/' . implode('/', $args);
            }

            // Tidy up.
            $rule = preg_replace('|/+|', '/', $rule);
            $rule = rtrim($rule, '/');

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
     * @param string $prefix
     * @return void
     * @throws ReflectionException
     */
    public static function addAutoRoutes(array &$routes, $class, $prefix = '')
    {
        self::addFromAttributes($routes, $class, $prefix);
        self::addFromNamespaces($routes, $class, $prefix);
    }


    /**
     * Add attribute routes from the target class/object.
     *
     * Use this to build a route table and then `load()` it into a router.
     * Or instead load the class routes directly into the router with `loadFrom()`.
     *
     * @param array $routes [ rule => target ]
     * @param string|object $class
     * * @param string $prefix
     * @return void
     * @throws ReflectionException
     */
    public static function addFromAttributes(array &$routes, $class, $prefix = '')
    {
        $auto = self::extractFromAttributes($class, $prefix);

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
     * @param string $prefix
     * @return void
     * @throws ReflectionException
     */
    public static function addFromNamespaces(array &$routes, $class, $prefix = '')
    {
        $auto = self::extractFromNamespaces($class, $prefix);

        foreach ($auto as $rule => $target) {
            $routes[$rule] = $target;
        }
    }
}

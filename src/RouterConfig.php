<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\router;

/**
 * The router config.
 *
 * @package karmabunny\router
 */
class RouterConfig
{

    /**
     * Which router implementation to use.
     *
     * @var string
     */
    public $mode = Router::MODE_CHUNKED;


    /**
     * Extract mode - to source routes from namespaces, attributes, both or none.
     *
     * - 'none' (0)
     * - 'namespaces' (1) - convert 'ns\controller\method' into rule names
     * - 'attributes' (2) - extract PHP8 attributes and @route comments
     * - 'all' (3) - extract all routes
     *
     * Additional flags:
     * - 'convert_regex' (128) convert extracted rule patterns to regex
     * - 'with_prefixes' (256) add prefixes to extracted routes
     * - 'short_namespaces' (512) only use controller/method in the rule path
     * - 'nested_prefixes' (256 + 1024) permit multiple controllers per prefix
     *
     * @var int
     */
    public $extract = Router::EXTRACT_ATTRIBUTES | Router::EXTRACT_WITH_PREFIXES;


    /**
     * Case sensitivity. By default - insensitive.
     *
     * @var bool
     */
    public $case_insensitive = true;


    /**
     * Permitted methods.
     *
     * @var string[]
     */
    public $methods = Router::METHODS;


    /**
     * (In chunked mode) - how many routes to jam into one regex.
     *
     * @var int
     */
    public $chunk_size = 10;


    /**
     * Use this the alter or clean the namespace rules.
     *
     * @var callable|null
     */
    public $edit_namespace_rule = [self::class, 'editNamespaceRule'];


    /**
     * Build a config.
     *
     * @param array $config
     */
    public function __construct($config)
    {
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }

        // Convert strings into a bitwise mask.
        if (is_string($extract = $config['extract'] ?? null)) {
            $extract = explode('|', $extract);

            static $REMAP = [
                'namespaces' => Router::EXTRACT_NAMESPACES,
                'attributes' => Router::EXTRACT_ATTRIBUTES,
                'all' => Router::EXTRACT_ALL,
                'prefixes' => Router::EXTRACT_WITH_PREFIXES,
                'short' => Router::EXTRACT_SHORT_NAMESPACES,
                'nested' => Router::EXTRACT_NESTED_PREFIXES,
                'none' => Router::EXTRACT_NONE,
            ];

            $this->extract = 0;

            foreach ($extract as $item) {
                $this->extract |= $REMAP[$item] ?? 0;
            }
        }

        if (!$this->extract) {
            $this->extract = Router::EXTRACT_NONE;
        }

        // Special case for namespace extracts.
        // These use ACTION methods, so make sure that's registered.
        if ($this->extract & Router::EXTRACT_NAMESPACES) {
            $this->methods[] = 'ACTION';
        }

        // Protect against bad configs.
        if (
            ($this->extract & Router::EXTRACT_CONVERT_REGEX)
            and $this->mode != Router::MODE_REGEX
        ) {
            $this->extract ^= Router::EXTRACT_CONVERT_REGEX;
        }
    }


    /**
     * Default namespace rule cleaner method.
     *
     * @param string $rule
     * @return string
     */
    public static function editNamespaceRule(string $rule)
    {
        static $remove = [
            '/app/',
            '/core/',
            '/module/',
            '/bloom/',
            '/controllers/',
            '-controller/',
            '/action-',
        ];

        return str_replace($remove, '/', $rule);
    }
}

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
     * String forms for the 'extract' bitmask.
     */
    const EXTRACT_KEYWORDS = [
        'namespaces' => Router::EXTRACT_NAMESPACES,
        'attributes' => Router::EXTRACT_ATTRIBUTES,
        'all' => Router::EXTRACT_ALL,
        'convert' => Router::EXTRACT_CONVERT_REGEX,
        'prefixes' => Router::EXTRACT_WITH_PREFIXES,
        'short' => Router::EXTRACT_SHORT_NAMESPACES,
        'nested' => Router::EXTRACT_NESTED_PREFIXES,
        'none' => Router::EXTRACT_NONE,
    ];


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
     * - 'convert' (128) convert extracted rule patterns to regex
     * - 'prefixes' (256) add prefixes to extracted routes
     * - 'short' (512) only use controller/method in the rule path
     * - 'nested' (256 + 1024) permit multiple controllers per prefix
     *
     * @var int
     */
    public $extract = Router::EXTRACT_ATTRIBUTES | Router::EXTRACT_WITH_PREFIXES;


    /**
     * Attribute extraction mode.
     *
     * - 'all' (3) - extract all attribute routes
     * - 'attributes' (1) - extract PHP8 attributes
     * - 'docs' (2) - extract @route doc comments
     *
     * @var int
     */
    public $attrs = Router::ATTR_ALL;


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
     * Doesn't do anything, just good for debugging in vardump().
     *
     * @var string
     */
    protected $_extract = '|';


    /**
     * Build a config.
     *
     * @param array $config
     */
    public function __construct($config)
    {
        // Convert strings into a bitwise mask.
        if (is_string($extract = $config['extract'] ?? null)) {
            $config['extract'] = self::parseExtract($extract);
        }

        foreach ($config as $key => $value) {
            $this->$key = $value;
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
        // Removing 'convert regex' if they've not set mode correctly.
        // TODO Should this throw instead?
        if (
            ($this->extract & Router::EXTRACT_CONVERT_REGEX)
            and $this->mode != Router::MODE_REGEX
        ) {
            $this->extract ^= Router::EXTRACT_CONVERT_REGEX;
        }

        // If they said 'yeah nested' but not the 'with prefixes' bit.
        // Force it on. This shouldn't ever be an error.
        if (
            ($this->extract & Router::EXTRACT_NESTED_PREFIXES)
            and !($this->extract & Router::EXTRACT_WITH_PREFIXES)
        ) {
            $this->extract |= Router::EXTRACT_WITH_PREFIXES;
        }

        // Build some debug stuff.
        foreach (self::EXTRACT_KEYWORDS as $name => $value) {
            if (($this->extract & $value) === $value) {
                $this->_extract .= $name . '|';
            }
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


    /**
     * Convert the a string form 'extract' into a bitmask that matches the
     * `Router::EXTRACT` flags.
     *
     * E.g. `'attributes|convert' => 2 + 128 = 130`
     *
     * @param string $extract
     * @return int
     */
    public static function parseExtract(string $extract): int
    {
        $parts = explode('|', $extract);
        $flags = 0;

        foreach ($parts as $item) {
            $flags |= self::EXTRACT_KEYWORDS[$item] ?? 0;
        }

        return $flags;
    }
}

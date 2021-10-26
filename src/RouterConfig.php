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
     * - 'none' or 0
     * - 'namespaces' or 1
     * - 'attributes' or 2
     * - 'both' or 3
     *
     * @var int
     */
    public $extract = Router::EXTRACT_ALL;


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
        if (is_string($this->extract)) {
            static $REMAP = [
                'namespaces' => Router::EXTRACT_NAMESPACES,
                'attributes' => Router::EXTRACT_ATTRIBUTES,
                'all' => Router::EXTRACT_ALL,
                'none' => Router::EXTRACT_NONE,
            ];

            $this->extract = $REMAP[$this->extract] ?? null;
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
}

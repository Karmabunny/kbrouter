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
    public $mode = Router::MODE_SINGLE;


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
    public $methods = ['HEAD', 'GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE'];


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
    }
}

<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace karmabunny\router;

/**
 *
 * @package karmabunny\router
 */
class RouterConfig
{

    /**
     *
     * @var string
     */
    public $mode = Router::MODE_SINGLE;


    /**
     *
     * @var bool
     */
    public $case_insensitive = true;


    /**
     *
     * @var string[]
     */
    public $methods = ['HEAD', 'GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE'];


    /**
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

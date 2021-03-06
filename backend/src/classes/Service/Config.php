<?php
namespace Neucore\Service;

/**
 * Wraps the config array to make it injectable.
 */
class Config
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return mixed
     */
    public function get(string $key, string $key2 = null)
    {
        $value = $this->config[$key] ?? null;

        if ($key2 !== null && $value !== null) {
            return $value[$key2] ?? null;
        }

        return $value;
    }
}

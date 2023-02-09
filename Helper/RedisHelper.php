<?php

namespace NitroPack\NitroPack\Helper;

use Magento\Setup\Validator\RedisConnectionValidator;

class RedisHelper
{
    /**
     * @var RedisConnectionValidator
     * */
    protected $redisValidator;
    /**
     * @var \Magento\Framework\App\DeploymentConfig
     * */
    protected $deploymentConfig;
    /**
     * @param  \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param RedisConnectionValidator $redisValidator
     * */
    public function __construct(
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        RedisConnectionValidator $redisValidator
    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->redisValidator = $redisValidator;
    }

    public function validatedRedisConnection()
    {
        $config = [
            'host' => $this->deploymentConfig->get('nitropack/cache/redis_host'),
            'db' => $this->deploymentConfig->get('nitropack/cache/redis_db'),
            'port' => $this->deploymentConfig->get('nitropack/cache/redis_port'),
            'pass' => empty($this->deploymentConfig->get('nitropack/cache/redis_pass')) ? null : $this->deploymentConfig->get('nitropack/cache/redis_pass'),

        ];
        if ($this->redisValidator->isValidConnection($config)) {
            return $config;
        }
        return false;
    }
}

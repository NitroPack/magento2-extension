<?php
/**
 * NitroPack
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the nitropack.io license that is
 * available through the world-wide-web at this URL:
 * https://github.com/NitroPack/magento2-extension/blob/716247d40d2de7b84f222c6a93761d87b6fe5b7b/LICENSE
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Site Optimization
 * @subcategory Performance
 * @package     NitroPack_NitroPack
 * @author      NitroPack Inc.
 * @copyright   Copyright (c) NitroPack (https://www.nitropack.io/)
 * @license     https://github.com/NitroPack/magento2-extension/blob/716247d40d2de7b84f222c6a93761d87b6fe5b7b/LICENSE
 */
namespace NitroPack\NitroPack\Helper;

use Magento\Setup\Validator\RedisConnectionValidator;

/**
 * Class RedisHelper - Redis connection validation helper for NitroPack
 * @extends \Magento\Framework\App\Helper\AbstractHelper
 * @package NitroPack\NitroPack\Helper
 * @since 2.1.0
 */
class RedisHelper extends \Magento\Framework\App\Helper\AbstractHelper
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

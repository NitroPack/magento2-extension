<?php

namespace NitroPack\NitroPack\Setup;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\Data\ConfigData;
use Magento\Framework\Config\Data\ConfigDataFactory;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Setup\ConfigOptionsListInterface;
use Magento\Framework\Setup\Option\TextConfigOption;
use Magento\Setup\Validator\RedisConnectionValidator;

class ConfigOptionsList implements ConfigOptionsListInterface
{
    private const NITROPACK_CACHE_STORAGE_TYPE = 'nitropack-cache-storage_type';
    private const NITROPACK_CACHE_STORAGE_TYPE_VALUE = 'redis';
    private const CONFIG_PATH__NITROPACK_CACHE_STORAGE_TYPE = 'nitropack/cache/storage_type';
    private const NITROPACK_CACHE_REDIS_HOST = 'nitropack-cache-redis_host';
    private const NITROPACK_CACHE_REDIS_HOST_DEFAULT = '127.0.0.1';
    private const CONFIG_PATH__NITROPACK_CACHE_REDIS_HOST = 'nitropack/cache/redis_host';
    private const NITROPACK_CACHE_REDIS_PORT = 'nitropack-cache-redis_port';
    private const NITROPACK_CACHE_REDIS_PORT_DEFAULT = '6379';
    private const CONFIG_PATH__NITROPACK_CACHE_REDIS_PORT = 'nitropack/cache/redis_port';
    private const NITROPACK_CACHE_REDIS_PASS = 'nitropack-cache-redis_pass';
    private const NITROPACK_CACHE_REDIS_PASS_DEFAULT = null;
    private const CONFIG_PATH__NITROPACK_CACHE_REDIS_PASS = 'nitropack/cache/redis_pass';
    private const NITROPACK_CACHE_REDIS_DB = 'nitropack-cache-redis_db';
    private const NITROPACK_CACHE_REDIS_DB_DEFAULT = null;
    private const CONFIG_PATH__NITROPACK_CACHE_REDIS_DB = 'nitropack/cache/redis_db';

    public const CONFIG_PATH_PAGE_CACHE_BACKEND = 'cache/frontend/page_cache/backend';

    /**
     * @var ConfigDataFactory
     */
    private $configDataFactory;

    /**
     * @var RedisConnectionValidator
     */
    private $redisValidator;

    /**
     * @param ConfigDataFactory $configDataFactory
     * @param RedisConnectionValidator $redisValidator
     */

    public function __construct(ConfigDataFactory $configDataFactory, RedisConnectionValidator $redisValidator)
    {
        $this->configDataFactory = $configDataFactory;
        $this->redisValidator = $redisValidator;
    }


    /**
     * @inheritDoc
     */
    public function getOptions(): array
    {
        return [
            new TextConfigOption(
                self::NITROPACK_CACHE_STORAGE_TYPE,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH__NITROPACK_CACHE_STORAGE_TYPE,
                'Storage Type Value',
                'redis'
            ),
            new TextConfigOption(
                self::NITROPACK_CACHE_REDIS_HOST,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH__NITROPACK_CACHE_REDIS_HOST,
                'Cache Redis Host Value',
                self::NITROPACK_CACHE_REDIS_HOST_DEFAULT
            ),
            new TextConfigOption(
                self::NITROPACK_CACHE_REDIS_PORT,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH__NITROPACK_CACHE_REDIS_PORT,
                'Cache redis Port Value',
                self::NITROPACK_CACHE_REDIS_PORT_DEFAULT
            ),
            new TextConfigOption(
                self::NITROPACK_CACHE_REDIS_PASS,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH__NITROPACK_CACHE_REDIS_PASS,
                'Storage Type Password',
                self::NITROPACK_CACHE_REDIS_PASS_DEFAULT
            ),
            new TextConfigOption(
                self::NITROPACK_CACHE_REDIS_DB,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH__NITROPACK_CACHE_REDIS_DB,
                'Storage Type DB Value',
                self::NITROPACK_CACHE_REDIS_DB_DEFAULT
            ),
        ];
    }

    /**
     * Map of option to config path relations.
     *
     * @var string[]
     */
    private static $map = [
        self::NITROPACK_CACHE_STORAGE_TYPE => self::CONFIG_PATH__NITROPACK_CACHE_STORAGE_TYPE,
        self::NITROPACK_CACHE_REDIS_HOST => self::CONFIG_PATH__NITROPACK_CACHE_REDIS_HOST,
        self::NITROPACK_CACHE_REDIS_PORT => self::CONFIG_PATH__NITROPACK_CACHE_REDIS_PORT,
        self::NITROPACK_CACHE_REDIS_PASS => self::CONFIG_PATH__NITROPACK_CACHE_REDIS_PASS,
        self::NITROPACK_CACHE_REDIS_DB => self::CONFIG_PATH__NITROPACK_CACHE_REDIS_DB,

    ];

    /**
     * @inheritdoc
     */
    public function createConfig(array $options, DeploymentConfig $deploymentConfig)
    {
        $configData = new ConfigData(ConfigFilePool::APP_ENV);
        $defaultHostChange =false;
        if (isset($options[self::NITROPACK_CACHE_REDIS_HOST]) && isset($options[self::NITROPACK_CACHE_REDIS_PORT])) {
            $config = [
                'host' => self::NITROPACK_CACHE_REDIS_HOST_DEFAULT,
                'port' => self::NITROPACK_CACHE_REDIS_PORT_DEFAULT,
                'db' => self::NITROPACK_CACHE_REDIS_DB_DEFAULT,
                'password' => self::NITROPACK_CACHE_REDIS_PASS_DEFAULT,
            ];
            if (!$this->redisValidator->isValidConnection($config)) {
                $config['host'] = 'redis';
                if ($this->redisValidator->isValidConnection($config)) {
                    $defaultHostChange = true;
                }
            }
        }

        foreach (self::$map as $inputKey => $configPath) {
            if (isset($options[$inputKey])) {
                if ($defaultHostChange && $configPath == self::CONFIG_PATH__NITROPACK_CACHE_REDIS_HOST) {
                    $configData->set($configPath, 'redis');
                } else {
                    $configData->set($configPath, $options[$inputKey]);
                }
            }
        }

        return [$configData];
    }

    /**
     * @var array
     */
    private $validPageCacheOptions = [
        self::NITROPACK_CACHE_STORAGE_TYPE_VALUE
    ];

    /**
     * @inheritdoc
     */
    public function validate(array $options, DeploymentConfig $deploymentConfig)
    {
        $errors = [];
        if (isset($options[self::NITROPACK_CACHE_REDIS_DB]) && !is_null($options[self::NITROPACK_CACHE_REDIS_DB])  && isset($options[self::NITROPACK_CACHE_STORAGE_TYPE])) {
            if ($options[self::NITROPACK_CACHE_STORAGE_TYPE] == self::NITROPACK_CACHE_STORAGE_TYPE_VALUE) {
                if (!$this->validateRedisConfig($options, $deploymentConfig)) {
                    $errors[] = 'Invalid Redis configuration. Could not connect to Redis server.';
                }
            }
        }

        return $errors;
    }

    /**
     * Validate that Redis connection succeeds for given configuration
     *
     * @param array $options
     * @param DeploymentConfig $deploymentConfig
     * @return bool
     */
    private function validateRedisConfig(array $options, DeploymentConfig $deploymentConfig)
    {
        $config = [];

        $config['host'] = isset($options[self::NITROPACK_CACHE_REDIS_HOST])
            ? $options[self::NITROPACK_CACHE_REDIS_HOST]
            : $deploymentConfig->get(
                self::CONFIG_PATH__NITROPACK_CACHE_REDIS_HOST,
                $this->getDefaultConfigValue(self::NITROPACK_CACHE_REDIS_HOST)
            );

        $config['port'] = isset($options[self::NITROPACK_CACHE_REDIS_PORT])
            ? $options[self::NITROPACK_CACHE_REDIS_PORT]
            : $deploymentConfig->get(
                self::CONFIG_PATH__NITROPACK_CACHE_REDIS_PORT,
                $this->getDefaultConfigValue(self::NITROPACK_CACHE_REDIS_PORT)
            );

        $config['db'] = isset($options[self::NITROPACK_CACHE_REDIS_DB])
            ? $options[self::NITROPACK_CACHE_REDIS_DB]
            : $deploymentConfig->get(
                self::CONFIG_PATH__NITROPACK_CACHE_REDIS_DB,
                $this->getDefaultConfigValue(self::NITROPACK_CACHE_REDIS_DB)
            );

        $config['password'] = isset($options[self::NITROPACK_CACHE_REDIS_PASS])
            ? $options[self::NITROPACK_CACHE_REDIS_PASS]
            : $deploymentConfig->get(
                self::CONFIG_PATH__NITROPACK_CACHE_REDIS_PASS,
                $this->getDefaultConfigValue(self::NITROPACK_CACHE_REDIS_PASS)
            );
        //Additional check for Magento Cloud
        if (!$this->redisValidator->isValidConnection($config)) {
            $config['host'] = 'redis';

            return $this->redisValidator->isValidConnection($config);
        }
        return $this->redisValidator->isValidConnection($config);
    }


    /**
     * Set default values for Redis configuration
     *
     * @param DeploymentConfig $deploymentConfig
     * @param ConfigData $configData
     * @return ConfigData
     */
    private function setDefaultRedisConfig(DeploymentConfig $deploymentConfig, ConfigData $configData)
    {
        foreach (self::$map as $inputKey => $configPath) {
            $configData->set($configPath, $deploymentConfig->get($configPath, $this->getDefaultConfigValue($inputKey)));
        }

        return $configData;
    }

    /**
     * Get the default value for input key
     *
     * @param string $inputKey
     * @return string
     */
    private function getDefaultConfigValue($inputKey)
    {
        if (isset($this->defaultConfigValues[$inputKey])) {
            return $this->defaultConfigValues[$inputKey];
        } else {
            return '';
        }
    }

    /**
     * Generate default cache ID prefix based on installation dir
     *
     * @return string
     */
    private function generateCachePrefix(): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return substr(\hash('sha256', dirname(__DIR__, 6)), 0, 3) . '_';
    }
}

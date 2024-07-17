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
namespace NitroPack\NitroPack\Controller\Adminhtml\System;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Setup\Validator\RedisConnectionValidator;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\App\DeploymentConfig\Writer as DeploymentConfigWriter;
use Magento\Framework\Config\File\ConfigFilePool;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;

/**
 * Class EnableRedisFullPageCache - Controller EnableRedisFullPageCache to enable the Redis Full Page Cache from dashboard page
 * @extends StoreAwareAction
 * @package NitroPack\NitroPack\Controller\Adminhtml\System
 * @since 3.2.0
 */
class EnableRedisFullPageCache extends Action
{
    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    protected $deploymentConfig;
    /**
     * @var RedisConnectionValidator
     * */
    protected $redisValidator;
    /**
     * @var Filesystem
     * */
    protected $fileSystem;
    /**
     * @var FileDriver
     * */
    protected $fileDriver;
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var DeploymentConfigWriter
     */
    protected $deploymentConfigWriter;

    public function __construct(
        Context                                 $context,
        DirectoryList                           $directoryList,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        JsonFactory                             $resultJsonFactory,
        RedisConnectionValidator                $redisValidator,
        Filesystem                              $fileSystem,
        FileDriver                              $fileDriver,
        DeploymentConfigWriter                  $deploymentConfigWriter
    )
    {
        parent::__construct($context);
        $this->directoryList = $directoryList;
        $this->deploymentConfig = $deploymentConfig;
        $this->redisValidator = $redisValidator;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->fileSystem = $fileSystem;
        $this->fileDriver = $fileDriver;
        $this->deploymentConfigWriter = $deploymentConfigWriter;
    }

    public function execute()
    {
        $configData = $this->deploymentConfig->getConfigData();
        if ($backendOptions = $this->deploymentConfig->get('cache/frontend/page_cache/backend_options')) {
            $configData['nitropack']['cache'] = [
                'redis_host' => $backendOptions['server'] == 'redis' ? 'localhost' : $backendOptions['server'],
                'redis_db' => $backendOptions['database'],
                'redis_port' => $backendOptions['port'] ?? 6379,
                'redis_pass' => $backendOptions['password'] ?? null
            ];
        }

        try {
            $this->deploymentConfigWriter->saveConfig([ConfigFilePool::APP_ENV => $configData]);
            return $this->resultJsonFactory->create()->setData(['save' => true]);
        } catch (\Exception $e) {
            return $this->resultJsonFactory->create()->setData(['save' => false]);
        }
    }
}

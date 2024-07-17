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
namespace NitroPack\NitroPack\Model\Cron;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\MessageQueue\ConnectionTypeResolver;
use Magento\Framework\ShellInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\PhpExecutableFinder;
/**
 * Class ConsumersRunner - Consumers Runner Model
 * @package NitroPack\NitroPack\Model\Cron
 * @since 2.0.0
 * */
class ConsumersRunner
{
    /**
     * Shell command line wrapper for executing command in background
     *
     * @var ShellInterface
     */
    private $shellBackground;



    /**
     * Application deployment configuration
     *
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * The executable finder specifically designed for the PHP executable
     *
     * @var PhpExecutableFinder
     */
    private $phpExecutableFinder;

    /**
     * @var ConnectionTypeResolver
     */
    private $mqConnectionTypeResolver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var LockManagerInterface
     */
    private $lockManager;

    /**
     * @var CheckIsAvailableMessagesInQueue
     */
    private $checkIsAvailableMessages;
    /**
     * @var \Magento\Framework\MessageQueue\DefaultValueProvider
     * */
    protected $defaultQueueValueProvider;

    protected $defaultQueueValueConnection;
    /**
     * @param PhpExecutableFinder $phpExecutableFinder The executable finder specifically designed
     *        for the PHP executable
     * @param DeploymentConfig $deploymentConfig The application deployment configuration
     * @param ShellInterface $shellBackground The shell command line wrapper for executing command in background
     * @param LockManagerInterface $lockManager The lock manager
     * @param ConnectionTypeResolver $mqConnectionTypeResolver Consumer connection resolver
     * @param LoggerInterface $logger Logger
     * @param \Magento\Framework\MessageQueue\DefaultValueProvider $defaultQueueValueProvider Logger
     * @param CheckIsAvailableMessagesInQueue $checkIsAvailableMessages
     */
    public function __construct(
        PhpExecutableFinder $phpExecutableFinder,
        DeploymentConfig $deploymentConfig,
        ShellInterface $shellBackground,
        \Magento\Framework\MessageQueue\DefaultValueProvider $defaultQueueValueProvider,
        LockManagerInterface $lockManager,
        ConnectionTypeResolver $mqConnectionTypeResolver = null,
        LoggerInterface $logger = null

    ) {
        $this->phpExecutableFinder = $phpExecutableFinder;
        $this->defaultQueueValueProvider = $defaultQueueValueProvider;
        $this->defaultQueueValueConnection = $this->defaultQueueValueProvider->getConnection();
        $this->deploymentConfig = $deploymentConfig;
        $this->shellBackground = $shellBackground;
        $this->lockManager = $lockManager;
        $this->mqConnectionTypeResolver = $mqConnectionTypeResolver
            ?: ObjectManager::getInstance()->get(ConnectionTypeResolver::class);
        $this->logger = $logger
            ?: ObjectManager::getInstance()->get(LoggerInterface::class);

    }
    /**
     * Runs consumers processes
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function run(): void
    {

        $php = $this->phpExecutableFinder->find() ?: 'php';

        $arguments = [$this->defaultQueueValueConnection=='amqp' && $this->deploymentConfig->get('queue/amqp') && count($this->deploymentConfig->get('queue/amqp')) > 0 ? 'nitropack.cache.queue.consumer' : 'nitropack.cache.queue.consumer.db'];


        $command = $php . ' ' . BP . '/bin/magento queue:consumers:start ';
        $this->shellBackground->execute($command, $arguments);
    }

}

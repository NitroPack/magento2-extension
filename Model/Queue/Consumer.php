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
namespace NitroPack\NitroPack\Model\Queue;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\CacheCleanHelper;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\DB\Adapter\DeadlockException;
use Magento\Framework\DB\Adapter\LockWaitException;
use Magento\Framework\Exception\LocalizedException;
use NitroPack\NitroPack\Logger\Logger;

/**
 * Class Consumer - Consumer Model
 * @package NitroPack\NitroPack\Model\Queue
 * @since 2.0.0
 * */
class Consumer
{

    /**
     * @var Logger
     */
    private $logger;

    const CONSUMER_NAME = "nitropack.queue.product.invalidation";

    const QUEUE_NAME = "nitropack.queue.product.invalidation";

    /**
     * @var \Magento\Framework\Json\Helper\Data
     * */
    protected $jsonHelper;
    /**
     * @var \NitroPack\NitroPack\Helper\CacheCleanHelper
     * */
    protected $cacheCleanHelper;
    /**
     * @var \NitroPack\NitroPack\Helper\InvalidationHelper
     * */
    protected $invalidationHelper;
    /**
     * @var ScopeConfigInterface
     * */
    protected $scopeConfig;

    /**
     * @param JsonHelper $jsonHelper
     * @param CacheCleanHelper $cacheCleanHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param \NitroPack\NitroPack\Helper\InvalidationHelper $invalidationHelper
     * @param Logger $logger
     */
    public function __construct(
        JsonHelper $jsonHelper,
        CacheCleanHelper $cacheCleanHelper,
        ScopeConfigInterface $scopeConfig,
        \NitroPack\NitroPack\Helper\InvalidationHelper $invalidationHelper,
        Logger $logger

    ) {
        $this->invalidationHelper = $invalidationHelper;
        $this->jsonHelper = $jsonHelper;
        $this->cacheCleanHelper = $cacheCleanHelper;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * consumer process start
     * @param string $request
     * @return string
     */
    public function process($request)
    {
        if ($this->scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK) == NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE) {
            $this->invalidationHelper->makeConnectionsDisableAndEnable(true);
        }

        $errorCode = null;
        $message = null;
        $serializedData = $request;

        $unserializedData = $this->jsonHelper->jsonDecode($serializedData);

        try {
            if ($unserializedData['action'] == 'invalidation') {
                return $this->cacheCleanHelper->invalidateCache(
                    $unserializedData['tag'],
                    $unserializedData['type'],
                    $unserializedData['reasonEntity'],
                    $unserializedData['storeId']
                );
            }
            if ($unserializedData['action'] == 'purge_tag') {
                return $this->cacheCleanHelper->purgeTagPageCache(
                    $unserializedData['tag'],
                    $unserializedData['type'],
                    $unserializedData['reasonEntity'],
                    $unserializedData['storeId']
                );
            }
            //add here your own logic for async operations
        } catch (\Zend_Db_Adapter_Exception  $e) {
            //here sample how to process exceptions if they occurred
            $this->logger->critical($e->getMessage());
            //you can add here your own type of exception when operation can be retried
            if (
                $e instanceof LockWaitException
                || $e instanceof DeadlockException
                || $e instanceof ConnectionException
            ) {
                $status = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = __($e->getMessage());
            } else {
                $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = __('Sorry, something went wrong during product prices update. Please see log for details.');
            }
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());

            $errorCode = $e->getCode();
            $message = __('Sorry, something went wrong during product prices update. Please see log for details.');
        }
    }


}

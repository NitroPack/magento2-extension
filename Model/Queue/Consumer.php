<?php

namespace NitroPack\NitroPack\Model\Queue;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use NitroPack\NitroPack\Helper\CacheCleanHelper;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\DB\Adapter\DeadlockException;
use Magento\Framework\DB\Adapter\LockWaitException;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Consumer
{

    /**
     * @var LoggerInterface
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

    public function __construct(
        JsonHelper $jsonHelper,
        CacheCleanHelper $cacheCleanHelper,
        ScopeConfigInterface $scopeConfig,
        \NitroPack\NitroPack\Helper\InvalidationHelper $invalidationHelper,
        LoggerInterface $logger

    ) {
        $this->invalidationHelper = $invalidationHelper;
        $this->jsonHelper = $jsonHelper;
        $this->cacheCleanHelper = $cacheCleanHelper;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->invalidationHelper->makeConnectionsDisableAndEnable(true);
    }

    /**
     * consumer process start
     * @param string $request
     * @return string
     */
    public function process($request)
    {
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

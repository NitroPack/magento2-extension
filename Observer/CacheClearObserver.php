<?php

namespace NitroPack\NitroPack\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use  \Magento\Framework\MessageQueue\PublisherInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Api\TaggingServiceInterface;

use NitroPack\SDK\PurgeType;

class CacheClearObserver implements ObserverInterface
{
    const TOPIC_NAME = 'nitropack.cache.queue.topic';
    const REASON_MANUAL_INVALIDATE_TYPE = "Manual invalidation of %s %s.";
    const REASON_MANUAL_INVALIDATE_ALL = "Manual invalidation of all store pages.";
    const REASON_MANUAL_PURGE_TYPE = "Manual purge of the %s %s.";
    const REASON_MANUAL_PURGE_ALL = "Manual purge of all store pages.";

    protected $objectManager;
    protected $logger;
    protected $request;
    protected $storeManager;

    protected $nitro;
    protected $tagger;

    protected $autoClearSettings;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $_json;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $_publisher;

    public function __construct(
        NitroServiceInterface $nitro,
        TaggingServiceInterface $tagger,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\Framework\Serialize\Serializer\Json $json
    ) {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->logger = $logger;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->nitro = $nitro;
        $this->tagger = $tagger;
        $this->_json = $json;
        $this->_publisher = $publisher;
        $storeId = $this->request->getParam('store');
        if ($storeId == 0) {
            $storeId = $this->storeManager->getDefaultStoreView()->getId();
        }
        $store = $this->storeManager->getStore($storeId);
        $this->nitro->reload($this->storeManager->getGroup($store->getStoreGroupId())->getCode());
    }

    public function execute(Observer $observer)
    {
        $eventName = $observer->getEvent()->getName();
        $callbackMethod = $this->getEventCallback($eventName);

        $this->autoClearSettings = $this->nitro->getSettings()->autoClear;

        if ($callbackMethod && method_exists($this, $callbackMethod) && $this->prepareData($observer)) {
            call_user_func_array(array($this, $callbackMethod), array($observer));
        }
    }

    protected function shouldAutoClear($type)
    {
        return (
            $this->nitro->isConnected() &&
            $this->nitro->isEnabled() &&
            isset($this->autoClearSettings->{$type}) && $this->autoClearSettings->{$type}
        );
    }

    protected function getEventCallback($eventName)
    {
        if (isset(static::$eventMap[$eventName])) {
            return static::$eventMap[$eventName];
        }
        return null;
    }

    protected function prepareData(Observer $observer)
    {
        return true;
    }

    protected function invalidateTag($tag, $reasonType, $reasonEntity)
    {
        $reason = sprintf(CacheClearObserver::REASON_MANUAL_INVALIDATE_TYPE, $reasonType, $reasonEntity);
        $this->logger->debug(sprintf('Invalidating tag %s because: %s', $tag, $reason));
        return $this->nitro->invalidateCache(null, $tag, $reason);
    }

    protected function purgeTagComplete($tag, $reasonType, $reasonEntity)
    {
        $reason = sprintf(CacheClearObserver::REASON_MANUAL_INVALIDATE_TYPE, $reasonType, $reasonEntity);
        $this->logger->debug(sprintf('Purging tag (complete) %s because: %s', $tag, $reason));
        return $this->nitro->purgeCache(null, $tag, PurgeType::COMPLETE, $reason);
    }

    protected function purgeTagPageCache($tag, $reasonType, $reasonEntity)
    {
        $reason = sprintf(CacheClearObserver::REASON_MANUAL_INVALIDATE_TYPE, $reasonType, $reasonEntity);
        $this->logger->debug(sprintf('Purging tag (page cache only) %s because: %s', $tag, $reason));
        return $this->nitro->purgeCache(null, $tag, PurgeType::PAGECACHE_ONLY, $reason);
    }

    protected function invalidateAll()
    {
        $this->logger->debug('Invalidating entire cache');
        return $this->nitro->invalidateCache(null, null, CacheClearObserver::REASON_MANUAL_INVALIDATE_ALL);
    }

    protected function purgeAllComplete()
    {
        $this->logger->debug('Purging entire cache (complete)');
        return $this->nitro->purgeCache(null, null, PurgeType::COMPLETE, CacheClearObserver::REASON_MANUAL_PURGE_ALL);
    }

    protected function purgeAllPageCache()
    {
        $this->logger->debug('Purging entire cache (page cache only)');
        return $this->nitro->purgeCache(
            null,
            null,
            PurgeType::PAGECACHE_ONLY,
            CacheClearObserver::REASON_MANUAL_PURGE_ALL
        );
    }

    protected function logEventData(Observer $observer)
    {
        $eventData = $observer->getEvent()->getData();
        $this->logger->debug('============');
        $this->logger->debug('Event: ' . $observer->getEvent()->getName());
        foreach ($eventData as $key => $datum) {
            if (is_object($datum)) {
                $this->logger->debug('    ' . $key . ': ' . get_class($datum));
            } else {
                if (is_string($datum) || is_numeric($datum)) {
                    $this->logger->debug('    ' . $key . ': ' . gettype($datum) . ':= ' . $datum);
                } else {
                    $this->logger->debug('    ' . $key . ': ' . gettype($datum));
                }
            }
        }
    }

}

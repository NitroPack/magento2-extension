<?php

namespace NitroPack\NitroPack\Helper;


use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Api\TaggingServiceInterface;
use NitroPack\NitroPack\Observer\CacheClearObserver;
use Psr\Log\LoggerInterface;
use NitroPack\SDK\PurgeType;

class CacheCleanHelper extends AbstractHelper
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;

    /**
     * @var TaggingServiceInterface
     * */
    protected $tagger;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        NitroServiceInterface $nitro,
        TaggingServiceInterface $tagger,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);

        $this->logger = $logger;
        $this->nitro = $nitro;
        $this->tagger = $tagger;
        $this->storeManager = $storeManager;
    }

    const REASON_MANUAL_INVALIDATE_TYPE = "Manual invalidation of %s %s.";
    const REASON_MANUAL_INVALIDATE_ALL = "Manual invalidation of all store pages.";
    const REASON_MANUAL_PURGE_TYPE = "Manual purge of the %s %s.";
    const REASON_MANUAL_PURGE_ALL = "Manual purge of all store pages.";

    public function invalidateCache($tag, $type, $reasonEntity, $storeId)
    {
        $this->reloadNitroPack($storeId);
        $reason = sprintf(self::REASON_MANUAL_INVALIDATE_TYPE, $type, $reasonEntity);
        $this->logger->debug(sprintf('Invalidating tag %s because: %s', $tag, $reason));
        try {
            return $this->nitro->getSdk()->invalidateCache(null, $tag, $reason);
        } catch (\Exception $e) {
            throw new $e->getMessage();
        }
    }

    public function purgeTagPageCache($tag, $reasonType, $reasonEntity, $storeId)
    {
        $this->reloadNitroPack($storeId);
        $reason = sprintf(self::REASON_MANUAL_INVALIDATE_TYPE, $reasonType, $reasonEntity);
        $this->logger->debug(sprintf('Purging tag (page cache only) %s because: %s', $tag, $reason));
        return $this->nitro->getSdk()->purgeCache(null, $tag, PurgeType::PAGECACHE_ONLY, $reason);
    }

    protected function purgeTagComplete($tag, $reasonType, $reasonEntity)
    {
        $reason = sprintf(CacheClearObserver::REASON_MANUAL_INVALIDATE_TYPE, $reasonType, $reasonEntity);
        $this->logger->debug(sprintf('Purging tag (complete) %s because: %s', $tag, $reason));
        return $this->nitro->getSdk()->purgeCache(null, $tag, PurgeType::COMPLETE, $reason);
    }

    public function reloadNitroPack($storeId)
    {
        $store = $this->storeManager->getStore($storeId);

        $storeGroupCode = $this->storeManager->getGroup($store->getStoreGroupId())->getCode();
        try {
            if ($storeId) {
                $this->nitro->reload($storeGroupCode);
                $this->nitro->getSdk()->setVarnishProxyCacheHeaders([
                    'X-Magento-Tags-Pattern' => ' .*'
                ]);
            }
        } catch (\Exception $e) {
            throw new  $e->getMessage();
        }
    }
}

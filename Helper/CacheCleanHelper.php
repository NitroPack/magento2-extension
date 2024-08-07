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

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Api\TaggingServiceInterface;
use NitroPack\NitroPack\Logger\Logger;
use NitroPack\SDK\PurgeType;

/**
 * Class CacheCleanHelper -  For Purging and Invalidation helper for NitroPack
 * @extends AbstractHelper
 * @package NitroPack\NitroPack\Helper
 * @since 2.1.0
 */

class CacheCleanHelper extends AbstractHelper
{
    /**
     * @var Logger
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
    /**
     * @var StoreManagerInterface
     * */
    protected $storeManager;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param NitroServiceInterface $nitro
     * @param TaggingServiceInterface $tagger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Logger $logger,
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
        if($storeId>0){
        $this->reloadNitroPack($storeId);
        $reason = sprintf(self::REASON_MANUAL_INVALIDATE_TYPE, $type, $reasonEntity);
        $this->logger->debug(sprintf('Invalidating tag %s because: %s', $tag, $reason));
        try {
            return $this->nitro->getSdk()->invalidateCache(null, $tag, $reason);
        } catch (Exception $e) {
            throw new $e->getMessage();
        }
        }
        return  '';
    }

    public function purgeTagPageCache($tag, $reasonType, $reasonEntity, $storeId)
    {
        if($storeId>0) {
            $this->reloadNitroPack($storeId);
            $reason = sprintf(self::REASON_MANUAL_INVALIDATE_TYPE, $reasonType, $reasonEntity);
            $this->logger->debug(sprintf('Purging tag (page cache only) %s because: %s', $tag, $reason));
            return $this->nitro->getSdk()->purgeCache(null, $tag, PurgeType::LIGHT_PURGE, $reason);
        }
        return '';
    }

    protected function purgeTagComplete($tag, $reasonType, $reasonEntity)
    {
        $reason = sprintf(self::REASON_MANUAL_INVALIDATE_TYPE, $reasonType, $reasonEntity);
        $this->logger->debug(sprintf('Purging tag (complete) %s because: %s', $tag, $reason));
        return $this->nitro->getSdk()->purgeCache(null, $tag, PurgeType::LIGHT_PURGE, $reason);
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
        } catch (Exception $e) {
            throw new  $e->getMessage();
        }
    }
}

<?php
/**
 * @author NitroPack Inc.
 * @copyright Copyright (c) NitroPack Inc. (https://www.nitropack.io)
 * @package NitroPack for Magento 2
 */

namespace NitroPack\NitroPack\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\Manager;

/**
 * class FastlyHelper
 * @package NitroPack\NitroPack\Helper
 */
class FastlyHelper extends AbstractHelper
{
    public const XML_PATH_CACHING_APPLICATION = 'system/full_page_cache/caching_application';
    public const XML_PATH_CACHING_APPLICATION_IS_NITROPACK_ENABLED = 'system/full_page_cache/enable_nitropack';
    public const FASTLY_CACHING_APPLICATION_VALUE = 42;
    public const FASTLY_MODULE_NAME = 'Fastly_Cdn';

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @param Context $context
     * @param Manager $moduleManager
     */
    public function __construct(
        Context $context,
        Manager $moduleManager
    )
    {
        $this->moduleManager = $moduleManager;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isFastlyCacheEnabled(): bool
    {
       return $this->scopeConfig->getValue(self::XML_PATH_CACHING_APPLICATION) == self::FASTLY_CACHING_APPLICATION_VALUE;
    }

    /**
     * @return bool
     */
    public function isNitroPackOptionEnabled(): bool
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CACHING_APPLICATION_IS_NITROPACK_ENABLED) == 1;
    }

    /**
     * @return bool
     */
    public function isFastlyModuleEnabled(): bool
    {
        return $this->moduleManager->isEnabled(self::FASTLY_MODULE_NAME);
    }

    /**
     * @return bool
     */
    public function isFastlyAndNitroPackEnabled(): bool
    {
        return $this->isFastlyCacheEnabled()
            && $this->isNitroPackOptionEnabled()
            && $this->isFastlyModuleEnabled();
    }

    public function isFastlyAndNitroDisable()
    {
        return $this->isFastlyCacheEnabled()
            && !$this->isNitroPackOptionEnabled();
    }

    public function purge($url = null)
    {
        if(class_exists('Fastly\Cdn\Model\PurgeCache')) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $purgeCache = $objectManager->get(\Fastly\Cdn\Model\PurgeCache::class);
            $purgeCache->sendPurgeRequest($url);
        }
        return ;
    }
}

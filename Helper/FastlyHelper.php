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

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\Manager;

/**
 * Class FastlyHelper -  For Fastly helper for NitroPack
 * @extends AbstractHelper
 * @package NitroPack\NitroPack\Helper
 * @since 2.9.0
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

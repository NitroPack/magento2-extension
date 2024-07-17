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
namespace NitroPack\NitroPack\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use NitroPack\NitroPack\Api\ConfigInterface;

/**
 * Class Config - Config Model
 * @package NitroPack\NitroPack\Model
 * @implements ConfigInterface
 * @since 2.0.0
 * */
class Config implements ConfigInterface
{
    public const XML_PATH_NITROPACK_PURGE_PRODUCT = 'nitropack/nitropack_purge/purge_product';
    public const XML_PATH_NITROPACK_PURGE_CATEGORY = 'nitropack/nitropack_purge/purge_category';
    public const XML_PATH_NITROPACK_PURGE_CMS_PAGE = 'nitropack/nitropack_purge/purge_cms_page';
    public const XML_PATH_NITROPACK_PURGE_CMS_BLOCK = 'nitropack/nitropack_purge/purge_cms_block';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function isPurgeProductEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_NITROPACK_PURGE_PRODUCT);
    }

    /**
     * @return bool
     */
    public function isPurgeCategoryEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_NITROPACK_PURGE_CATEGORY);
    }

    /**
     * @return bool
     */
    public function isPurgeCmsPageEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_NITROPACK_PURGE_CMS_PAGE);
    }

    /**
     * @return bool
     */
    public function isPurgeCmsBlockEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_NITROPACK_PURGE_CMS_BLOCK);
    }
}


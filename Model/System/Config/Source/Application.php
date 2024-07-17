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
namespace NitroPack\NitroPack\Model\System\Config\Source;

use Magento\PageCache\Model\Config;

/**
 * Class Application - Application Config Source Model
 * @package NitroPack\NitroPack\Model\ResourceModel
 * @since 2.1.0
 * @extends \Magento\PageCache\Model\System\Config\Source\Application
 **/
class Application extends \Magento\PageCache\Model\System\Config\Source\Application
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Config::BUILT_IN,
                'label' => __('Built-in Cache')
            ],
            [
                'value' => Config::VARNISH,
                'label' => __('Varnish Cache')
            ],
            [
                'value' => \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE,
                'label' => __('NitroPack')
            ]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            Config::BUILT_IN => __('Built-in Cache'),
            Config::VARNISH => __('Varnish Cache'),
            \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE => __('NitroPack')
        ];
    }
}

<?php

namespace NitroPack\NitroPack\Model\System\Config\Source;

use Magento\PageCache\Model\Config;

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

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
namespace NitroPack\NitroPack\Model\FullPageCache;


/**
 * Class PurgeCache - Purge Cache
 * @implements PurgeInterface
 * @package NitroPack\NitroPack\Model\FullPageCache
 * @since 2.9.0
 * */
class PurgeCache implements PurgeInterface
{
    /**
     * @var \NitroPack\NitroPack\Helper\VarnishHelper
     * */
    public $varnishHelper;
    /**
     * @var \NitroPack\NitroPack\Helper\FastlyHelper
     * */
    public $fastlyHelper;

    /**
     * @var \NitroPack\NitroPack\Model\FullPageCache\IntegratedCacheInterface
     * */
    protected $integratedCache;
    public function __construct(
        \NitroPack\NitroPack\Helper\VarnishHelper $varnishHelper,
        \NitroPack\NitroPack\Helper\FastlyHelper $fastlyHelper,
        IntegratedCacheInterface $integratedCache

    ) {
        $this->integratedCache = $integratedCache;
        $this->varnishHelper = $varnishHelper;
        $this->fastlyHelper = $fastlyHelper;
    }


    public function purge($url = null)
    {

        $this->integratedCache->setIntegratedCache();
        if ($this->integratedCache->getIntegratedCache() == 'varnish') {
           return  $this->varnishHelper->purgeVarnish($url);
        }

        if ($this->integratedCache->getIntegratedCache() == 'fastly') {
            return  $this->fastlyHelper->purge($url);
        }

    }

}

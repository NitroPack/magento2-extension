<?php

namespace NitroPack\NitroPack\Model\FullPageCache;


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

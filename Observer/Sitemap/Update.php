<?php

namespace NitroPack\NitroPack\Observer\Sitemap;

use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\ApiHelper;
use NitroPack\NitroPack\Helper\FastlyHelper;
use NitroPack\NitroPack\Helper\SitemapHelper;

class Update implements ObserverInterface
{
    /**
     * @var \Magento\Store\Api\GroupRepositoryInterface
     * */
    protected $groupRepository;
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;
    /**
     * @var StateInterface
     * */
    protected $_cacheState;
    /**
     * @var ApiHelper
     * */
    protected $apiHelper;
    /**
     * @var SitemapHelper
     * */
    protected $sitemapHelper;
    /**
     * @var FastlyHelper
     * */
    protected $fastlyHelper;
    public function __construct(ApiHelper $apiHelper,FastlyHelper $fastlyHelper,SitemapHelper $sitemapHelper, \Magento\Store\Api\GroupRepositoryInterface $groupRepository, ScopeConfigInterface $scopeConfig, StateInterface $_cacheState)
    {

        $this->apiHelper = $apiHelper;
        $this->sitemapHelper = $sitemapHelper;
        $this->groupRepository = $groupRepository;
        $this->_scopeConfig = $scopeConfig;
        $this->_cacheState = $_cacheState;
        $this->fastlyHelper = $fastlyHelper;
    }

    public function execute(Observer $observer)
    {

        $storeGroup = $this->groupRepository->getList();
        if ($this->_scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK)
            && in_array($this->_scopeConfig->getValue(
                NitroService::FULL_PAGE_CACHE_NITROPACK
            ), [NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE, NitroService::FASTLY_CACHING_APPLICATION_VALUE]) && $this->_cacheState->isEnabled('full_page')) {

            if($this->fastlyHelper->isFastlyAndNitroDisable()){

                return false;
            }
            foreach ($storeGroup as $storesData) {

                $settingsFilename = $this->apiHelper->getSettingsFilename($storesData->getCode());
                $haveData = $this->apiHelper->readFile($settingsFilename);
                if ($haveData) {
                    $settings = json_decode($haveData);


                    if (isset($settings->enabled) && $settings->enabled &&  isset($settings->cacheWarmup) && $settings->cacheWarmup) {
                        $this->sitemapHelper->getSiteMapPath($storesData->getId(), $storesData->getCode(), $settings);
                    }

                }
            }

        }
    }
}

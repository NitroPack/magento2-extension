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
namespace NitroPack\NitroPack\Observer\Sitemap;

use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\ApiHelper;
use NitroPack\NitroPack\Helper\FastlyHelper;
use NitroPack\NitroPack\Helper\SitemapHelper;
use \Magento\Framework\Message\ManagerInterface;

/**
 * Class Update - Sitemap Update Observer
 * @implements ObserverInterface
 * @package NitroPack\NitroPack\Observer\Sitemap
 * @since 3.1.0
 * */
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

    protected $messageManager;

    public function __construct(
        ApiHelper $apiHelper,
        FastlyHelper $fastlyHelper,
        SitemapHelper $sitemapHelper,
        \Magento\Store\Api\GroupRepositoryInterface $groupRepository,
        ScopeConfigInterface $scopeConfig,
        StateInterface $_cacheState,
        ManagerInterface $messageManager
    )
    {

        $this->apiHelper = $apiHelper;
        $this->sitemapHelper = $sitemapHelper;
        $this->groupRepository = $groupRepository;
        $this->_scopeConfig = $scopeConfig;
        $this->_cacheState = $_cacheState;
        $this->fastlyHelper = $fastlyHelper;
        $this->messageManager = $messageManager;
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
                       $result = $this->sitemapHelper->getSiteMapPath($storesData->getId(), $storesData->getCode(), $settings);
                       if (!$result) {
                           $this->messageManager->addErrorMessage('NitroPack sitemap generation failed due to pub/media folder permissions.');
                       }
                    }

                }
            }

        }
    }
}

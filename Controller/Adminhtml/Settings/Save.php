<?php

namespace NitroPack\NitroPack\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;

use Magento\Store\Model\Store;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Api\NitroServiceInterface;

use NitroPack\NitroPack\Helper\AdminFrontendUrl;
use NitroPack\NitroPack\Helper\NitroPackConfigHelper;
use NitroPack\NitroPack\Helper\SitemapHelper;
use NitroPack\NitroPack\Model\FullPageCache\PurgeInterface;

class Save extends StoreAwareAction
{

    const NITRO_CLEAR_ARRAY = '___NITRO_CLEAR_ARRAY';
    /**
     * @var RequestInterface
     * */
    protected $request;
    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;
    /**
     * @var AdminFrontendUrl
     * */
    protected $urlHelper;
    /**
     * @var NitroPackConfigHelper
     * */
    protected $nitroPackConfigHelper;
    /**
     * @var SitemapHelper
     * */
    protected $sitemapHelper;
    /**
     * @var JsonFactory
     * */
    protected $resultJsonFactory;

    protected $store = null;
    /**
     * @var PurgeInterface
     * */
    protected $purgeInterface;

    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param NitroServiceInterface $nitro
     * @param AdminFrontendUrl $urlHelper
     * @param SitemapHelper $sitemapHelper
     * @param PurgeInterface $purgeInterface
     * @param NitroPackConfigHelper $nitroPackConfigHelper
     * */
    public function __construct(
        Context               $context,
        RequestInterface      $request,
        NitroServiceInterface $nitro,
        AdminFrontendUrl      $urlHelper,
        SitemapHelper         $sitemapHelper,
        PurgeInterface        $purgeInterface,
        NitroPackConfigHelper $nitroPackConfigHelper
    )
    {
        parent::__construct($context, $nitro);
        $this->request = $request;
        $this->nitro = $nitro;
        $this->nitroPackConfigHelper = $nitroPackConfigHelper;
        $this->sitemapHelper = $sitemapHelper;
        $this->urlHelper = $urlHelper;
        $this->purgeInterface = $purgeInterface;
        $this->resultJsonFactory = $this->_objectManager->create(JsonFactory::class);
        $this->store = $this->getStoreGroup();
    }

    /**
     * @param string $event
     * @param array $additional_meta_data
     * @return array
     */
    public function triggerConfigureEvent(string $event, array $additional_meta_data): array
    {
        $eventUrl = $this->nitro->integrationUrl('extensionEvent');
        $eventSent = $this->nitro->nitroEvent($event, $eventUrl, $this->store, $additional_meta_data);
        return array($eventUrl, $eventSent);
    }

    protected function nitroExecute()
    {
        $shouldSave = false;
        $errors = array();
        $resultData = array();

        $booleans = array(
            'enabled',
            'cacheWarmup',
            'safeMode',
            'gzip',
            'cache_to_login_customer',
        );

        $arrays = array();

        $oldSettings = (array)$this->nitro->getSettings();
        $additional_meta_data = [];
        foreach ($booleans as $option) {

            if (($value = $this->request->getPostValue($option, null)) !== null) {
                //

                if ($option === 'enabled') {
                    $event = $value ? 'enable_extension' : 'disable_extension';
                    $eventUrl = $this->nitro->integrationUrl('extensionEvent');
                    $eventSent = $this->nitro->nitroEvent($event, $eventUrl, $this->store);
                    $resultData['event'] = $eventSent;
                } else {
                    array_push(
                        $additional_meta_data,
                        ['setting' => $option, 'before' => isset($oldSettings[$option]) ?: null, 'after' => $value]
                    );
                }
                if ($option === 'enabled' && $value) {
                    $this->nitroPackConfigHelper->varnishConfiguredSetup();
                }
                if ($option == 'gzip') {
                    if ($value) {
                        $this->nitro->getSdk()->enableCompression();
                    } else {
                        $this->nitro->getSdk()->disableCompression();
                    }
                }
                //EVENT TRIGGER
                if ($option === 'enabled') {
                    $this->nitroPackConfigHelper->setBoolean('previous_extension_status', $value);

                }

                if ($option === 'cache_to_login_customer') {
                     $this->nitro->purgeCache(
                        null,
                        null,
                        \NitroPack\SDK\PurgeType::COMPLETE,
                        'Cache purge Because Cache setting changed'
                    );
                    $this->purgeInterface->purge();

                    if (!$value) {
                        $this->nitro->getSdk()->getApi()->unsetVariationCookie('X-Magento-Vary');
                    }
                    if ($value) {
                        $xMagentoVary = (array)$oldSettings['x_magento_vary'];
                        if (count($xMagentoVary) > 0) {
                            $this->nitro->getSdk()->getApi()->setVariationCookie('X-Magento-Vary', array_keys($xMagentoVary), 1);
                        }
                    }
                }
                $this->nitroPackConfigHelper->setBoolean($option, $value);

                $shouldSave = true;
            }

        }
        if (empty($errors) && $shouldSave) {
            $this->nitro->persistSettings();
            $newSettings = (array)$this->nitro->getSettings();
            if (!$oldSettings['cacheWarmup'] && $newSettings['cacheWarmup']) {
                $sitemapUrl = $this->getWarmupSitemapUrl(
                    $this->getStoreGroup()->getId(),
                    $this->getStoreGroup()->getCode(),
                    $this->nitro->getSettings()
                );
                $this->nitro->getApi()->setWarmupHomepage($this->getStoreUrl());
                $this->nitro->getApi()->setWarmupSitemap($sitemapUrl);
                $this->nitro->getApi()->enableWarmup();
                $this->nitro->getApi()->resetWarmup();
            } elseif ($oldSettings['cacheWarmup'] && !$newSettings['cacheWarmup']) {
                $this->nitro->getApi()->unsetWarmupSitemap();
                $this->nitro->getApi()->disableWarmup();
                $this->nitro->getApi()->resetWarmup();
            }
        }
        $resultData['saved'] = true;
        return $this->resultJsonFactory->create()->setData($resultData);
    }


    protected function setArray($option, $value)
    {
        if (strpos($option, '-') === false) {
            $this->nitro->getSettings()->{$option} = $value;
            return;
        }

        $ref = $this->nitro->getSettings();
        $split = explode('-', $option);
        $last = count($split) - 1;

        foreach ($split as $i => $sub) {
            if ($i != $last) {
                $ref = $ref->{$sub};
            } else {
                $ref->{$sub} = $value;
            }
        }
    }

    protected function getWarmupSitemapUrl($storeGroupId, $storeGroupCode, $nitroSetting)
    {
        return $this->sitemapHelper->getSiteMapPath($storeGroupId, $storeGroupCode, $nitroSetting);
    }

    public function getStoreUrl()
    {
        $url = '';
        $store = $this->_objectManager->get(Store::class);
        $defaultStoreView = $this->storeManager->getStore($this->storeGroup->getDefaultStoreId());
        $url = $store->isUseStoreInUrl() ? str_replace(
            $defaultStoreView->getCode() . '/',
            '',
            $defaultStoreView->getBaseUrl()
        ) : $defaultStoreView->getBaseUrl(); // get store view name
        return $url;
    }
}

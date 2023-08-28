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
     * @param Context $context ,
     * @param RequestInterface $request ,
     * @param NitroServiceInterface $nitro ,
     * @param AdminFrontendUrl $urlHelper ,
     * @param SitemapHelper $sitemapHelper ,
     * @param NitroPackConfigHelper $nitroPackConfigHelper
     * */
    public function __construct(
        Context $context,
        RequestInterface $request,
        NitroServiceInterface $nitro,
        AdminFrontendUrl $urlHelper,
        SitemapHelper $sitemapHelper,
        NitroPackConfigHelper $nitroPackConfigHelper
    ) {
        parent::__construct($context, $nitro);
        $this->request = $request;
        $this->nitro = $nitro;
        $this->nitroPackConfigHelper = $nitroPackConfigHelper;
        $this->sitemapHelper = $sitemapHelper;
        $this->urlHelper = $urlHelper;
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
            'autoClear-products',
            'autoClear-attributes',
            'autoClear-attributeSets',
            'autoClear-reviews',
            'autoClear-categories',
            'autoClear-pages',
            'autoClear-blocks',
            'autoClear-widgets',
            'autoClear-orders',
            'pageTypes-home',
            'pageTypes-product',
            'pageTypes-category',
            'pageTypes-info',
            'pageTypes-contact',
            'cache_to_login_customer',
            'warmupTypes-home',
            'warmupTypes-product',
            'warmupTypes-category',
            'warmupTypes-info',
            'warmupTypes-contact'
        );

        $arrays = array(
            'pageTypes-custom'
        );

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
                //EVENT TRIGGER
                $this->nitroPackConfigHelper->setBoolean('previous_extension_status', $value);

                $this->nitroPackConfigHelper->setBoolean($option, $value);
                $shouldSave = true;
            }

        }

        foreach ($arrays as $option) {
            $value = $this->getRequest()->getPostValue($option, null);
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $this->setArray($option, $value);
                $shouldSave = true;
                array_push(
                    $additional_meta_data,
                    ['setting' => $option, 'before' => isset($oldSettings[$option]) ?: null, 'after' => $value]
                );
            } elseif ($value === "") {
                $this->setArray($option, array());
                $shouldSave = true;
                array_push(
                    $additional_meta_data,
                    ['setting' => $option, 'before' => isset($oldSettings[$option]) ?: null, 'after' => $value]
                );
            } else {
                $resultData['nope'] = $value;
                return $this->resultJsonFactory->create()->setData($resultData);
            }
        }

        if (empty($errors) && $shouldSave) {
            $event = 'configure';
            $this->triggerConfigureEvent($event, $additional_meta_data);
            $this->nitro->persistSettings();
            $newSettings = (array)$this->nitro->getSettings();

            if (!$oldSettings['cacheWarmup'] && $newSettings['cacheWarmup']) {
                $sitemapUrl = $this->getWarmupSitemapUrl(
                    $this->getStoreGroup()->getId(),
                    $this->getStoreGroup()->getCode(),
                    $this->nitro
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

    protected function getWarmupSitemapUrl($storeGroupId, $storeGroupCode, $nitro)
    {
        return $this->sitemapHelper->getSiteMapPath($storeGroupId, $storeGroupCode, $nitro);
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

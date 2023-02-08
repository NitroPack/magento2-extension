<?php

namespace NitroPack\NitroPack\Block;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;

use NitroPack\NitroPack\Api\NitroServiceInterface;

class ConnectBlock extends Template
{

    protected $nitro;

    protected $storeGroup;
    /**
     * @var UrlInterface
     * */
    protected $_backendUrl;
    /**
     * @var StoreManagerInterface
     * */
    protected $_storeManager;
    /**
     * @var Store
     * */
    protected $store;

    public function __construct(
        Context $context, // required as part of the Magento\Backend\Block\Template constructor
        NitroServiceInterface $nitro, // dependency injection'ed
        UrlInterface $backendUrl, // dependency injection'ed
        StoreManagerInterface $storeManager, // dependency injection'ed
        RequestInterface $request, // dependency injection'ed
        Store $store,
        array $data = [] // required as part of the Magento\Backend\Block\Template constructor
    )
    {
        parent::__construct($context, $data);
        $this->store = $store;
        $this->nitro = $nitro;
        $this->_backendUrl = $backendUrl;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
    }

    public function getSaveUrl()
    {
        $currentUrl = $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
        return strpos($currentUrl, '/again/') !== false ? $this->_backendUrl->getUrl(
            'NitroPack/connect/AgainUpdateCredential',
            array(
                'group' => $this->getStoreGroup()->getId()
            )
        ) :
            $this->_backendUrl->getUrl('NitroPack/connect/save', array(
                'group' => $this->getStoreGroup()->getId()
            ));
    }

    public function getStoreUrl()
    {
        $url = '';

        $defaultStoreView = $this->_storeManager->getStore($this->storeGroup->getDefaultStoreId());
        $url = $this->store->isUseStoreInUrl() ? str_replace(
            $defaultStoreView->getCode() . '/',
            '',
            $defaultStoreView->getBaseUrl()
        ) : $defaultStoreView->getBaseUrl(); // get store view name
        return $url;
    }

    public function getStoreName()
    {
        return $this->getStoreGroup()->getName();
    }

    public function getStoreCode()
    {
        return $this->getStoreGroup()->getCode();
    }

    protected function getStoreGroup()
    {
        if (!$this->storeGroup) {
            $storeGroupId = (int)$this->getRequest()->getParam('group');
            if ($storeGroupId == 0) {
                // This happens when the user has selected "All store views", use the default configured store
                // @TODO the user should be notified that they're editing the settings for the default store view, not all store views
                $storeGroupId = $this->_storeManager->getGroup()->getId();

                $this->usedDefaultStore = true;
            }
            $this->storeGroup = $this->_storeManager->getGroup($storeGroupId);
        }
        return $this->storeGroup;
    }
}

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

namespace NitroPack\NitroPack\Block;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use Magento\Framework\View\Asset\Repository;
/**
 * Class ConnectBlock - Block for the NitroPack Connect admin dashboard
 * @extends Template
 * @package NitroPack\NitroPack\Block
 * @since 3.0.0
 */
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

    protected $assetRepository;

    /**
     * @param  Context $context
     * @param  NitroServiceInterface $nitro
     * @param  UrlInterface $backendUrl
     * @param  StoreManagerInterface $storeManager
     * @param  RequestInterface $request
     * @param  Store $store,
     * @param  array $data
     * */
    public function __construct(
        Context $context, // required as part of the Magento\Backend\Block\Template constructor
        NitroServiceInterface $nitro, // dependency injection'ed
        UrlInterface $backendUrl, // dependency injection'ed
        StoreManagerInterface $storeManager, // dependency injection'ed
        RequestInterface $request, // dependency injection'ed
        Store $store,
        Repository $assetRepository,
        array $data = [] // required as part of the Magento\Backend\Block\Template constructor
    )
    {
        parent::__construct($context, $data);
        $this->store = $store;
        $this->nitro = $nitro;
        $this->_backendUrl = $backendUrl;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
        $this->assetRepository = $assetRepository;
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


    public function getImage($imageName)
    {
        return $this->assetRepository->getUrl("NitroPack_NitroPack::img/$imageName");
    }
}

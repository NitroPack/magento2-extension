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
namespace NitroPack\NitroPack\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
/**
 * Class StoreAwareAction - Abstract class to handle store-aware actions
 * @extends Action
 * @abstract
 * @package NitroPack\NitroPack\Controller\Adminhtml
 * @since 2.0.0
 */
abstract class StoreAwareAction extends Action
{

    protected $nitro = null;
    protected $storeGroup = null;
    protected $storeManager = null;
    protected $usedDefaultStore = false;

    /**
     * @param Context $context
     * @param NitroServiceInterface $nitro
     * */
    public function __construct(
        Context $context,
        NitroServiceInterface $nitro

    ) {

        parent::__construct($context);
        $this->nitro = $nitro;
        $this->storeManager = $this->_objectManager->get(StoreManagerInterface::class);
    }

    public function execute()
    {

        $storeGroupId = (int)$this->getRequest()->getParam('group');

        if ($storeGroupId == 0) {
            // This happens when the user has selected "All store views", use the default configured store
            // @TODO the user should be notified that they're editing the settings for the default store view, not all store views
            $storeGroupId = $this->storeManager->getGroup()->getId();
            $this->usedDefaultStore = true;
        }

        $this->storeGroup = $this->storeManager->getGroup($storeGroupId);
        $fileDriver = $this->_objectManager->get(\Magento\Framework\Filesystem\Driver\File::class);
        $settingsFilename = $this->nitro->getSettingsFilename($this->storeGroup->getCode());
        try {
            $this->nitro->reload($this->storeGroup->getCode());
            if($this->nitro->isConnected()){
            $this->nitro->getSdk()->getApi()->getDiskUsage();
            }
        } catch (\Exception $e) {
            if (strpos(strtolower($e->getMessage()), 'not reliable') !== false) {
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath($this->_backendUrl->getUrl('NitroPack/connect/support', []));
            }
            if ($fileDriver->isExists($settingsFilename)) {
                $this->messageManager->addErrorMessage("Nitropack:" . $e->getMessage());
                if (strpos(strtolower($e->getMessage()), 'verification') !== false) {
                    $resultRedirect = $this->resultRedirectFactory->create();
                    return $resultRedirect->setPath(
                        $this->_backendUrl->getUrl('NitroPack/connect/again', ['group' => $this->storeGroup->getId()])
                    );
                } else {
                    $resultRedirect = $this->resultRedirectFactory->create();
                    return $resultRedirect->setPath($this->_backendUrl->getUrl('NitroPack/connect/support', []));
                }
            } else {
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath(
                    $this->_backendUrl->getUrl('NitroPack/connect/index', ['group' => $this->storeGroup->getId()])
                );
            }
        }

        return $this->nitroExecute();
    }

    public function getUrlWithStore($routePath = null, $routeParams = null)
    { // returns an admin URL
        if ($routeParams == null) {
            $routeParams = array();
        }
        $routeParams['group'] = $this->storeGroup->getId();
        return $this->_backendUrl->getUrl($routePath, $routeParams);
    }

    protected function getStoreGroup()
    {
        return $this->storeGroup;
    }

    protected abstract function nitroExecute();

}

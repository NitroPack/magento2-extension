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

namespace NitroPack\NitroPack\Controller\Adminhtml\Connect;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;

use NitroPack\NitroPack\Helper\AdminFrontendUrl;
/**
 * Class Again - Again Controller to connect again to NitroPack
 * @extends Action
 * @package NitroPack\NitroPack\Controller\Adminhtml\Connect
 * @since 2.1.0
 */
class Again extends Action
{
    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;
    /**
     * @var PageFactory
     * */
    protected $resultPageFactory;
    /**
     * @var AdminFrontendUrl
     * */
    protected $urlHelper;
    /**
     * @var StoreManagerInterface
     * */
    protected $storeManager;
    protected $storeGroup = null;
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param AdminFrontendUrl $urlHelper
     * @param StoreManagerInterface $storeManager
     * @param NitroServiceInterface $nitro
     * */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        AdminFrontendUrl $urlHelper,
        StoreManagerInterface $storeManager,
        NitroServiceInterface $nitro
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->nitro = $nitro;
        $this->storeManager = $storeManager;
        $this->urlHelper = $urlHelper;
    }

    public function execute()
    {

        $storeGroupId = (int)$this->getRequest()->getParam('group');
        if ($storeGroupId != 0) {
            $this->storeGroup = $this->storeManager->getGroup($storeGroupId);
            try {
                $this->nitro->reload($this->storeGroup->getCode());
            } catch (\Exception $e) {
                if (strpos(strtolower($e->getMessage()), 'verification') !== false) {
                    return $this->resultPageFactory->create();
                }
                $fileDriver = $this->_objectManager->get(\Magento\Framework\Filesystem\Driver\File::class);
                $settingsFilename = $this->nitro->getSettingsFilename($this->storeGroup->getCode());
                if (!$fileDriver->isExists($settingsFilename)) {
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $resultRedirect->setUrl(
                        $this->_backendUrl->getUrl('NitroPack/connect/index', ['group' => $this->storeGroup->getId()])
                    );
                    return $resultRedirect;
                }
            }

            if ($this->nitro->isConnected()) {
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setUrl(
                    $this->_backendUrl->getUrl('NitroPack/settings/index', ['group' => $this->storeGroup->getId()])
                );
                return $resultRedirect;
            }

        }
        return $this->resultPageFactory->create();
    }
}

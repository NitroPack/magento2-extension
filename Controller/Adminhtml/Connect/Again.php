<?php

namespace NitroPack\NitroPack\Controller\Adminhtml\Connect;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\AdminFrontendUrl;

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

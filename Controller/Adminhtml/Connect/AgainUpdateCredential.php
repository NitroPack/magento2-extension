<?php

namespace NitroPack\NitroPack\Controller\Adminhtml\Connect;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\AdminFrontendUrl;
use NitroPack\NitroPack\Helper\NitroPackConfigHelper;
use Magento\Store\Model\StoreManagerInterface;

class AgainUpdateCredential extends Action
{
    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;
    /**
     * @var AdminFrontendUrl
     * */
    protected $urlHelper;
    /**
     * @var JsonFactory
     * */
    protected $resultJsonFactory;
    /**
     * @var $this
     * */
    protected $request;

    protected $errors = array();
    /**
     * @var StoreManagerInterface
     * */
    protected $storeManager;
    /**
     * @var NitroPackConfigHelper
     * */
    protected $_helper;
    protected $store;

    public function __construct(
        Context $context,
        NitroServiceInterface $nitro,
        AdminFrontendUrl $urlHelper,
        NitroPackConfigHelper $_helper,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->nitro = $nitro;
        $this->resultJsonFactory = $this->_objectManager->create(JsonFactory::class);
        $this->urlHelper = $urlHelper;
        $this->_helper = $_helper;
        $this->request = $this->getRequest();
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        $nitroSiteId = trim($this->request->getPostValue('nitro_site_id', ""));
        $nitroSiteSecret = trim($this->request->getPostValue('nitro_site_secret', ""));
        if ($this->_helper->validateSiteCredentials($nitroSiteId, $nitroSiteSecret)) {
            $storeGroup = $this->storeManager->getGroup();

            try {
                $this->_helper->saveSettings($nitroSiteId, $nitroSiteSecret, $storeGroup->getCode());
                $this->nitro->reload($storeGroup->getCode());
                $eventUrl = $this->nitro->integrationUrl('extensionEvent');
                $this->nitro->nitroEvent('connect', $eventUrl, $storeGroup);
                $eventSent = $this->nitro->nitroEvent('enable_extension', $eventUrl, $storeGroup);
                return $this->resultJsonFactory->create()->setData(array(
                    'connected' => true,
                    'redirect' => $this->_backendUrl->getUrl('NitroPack/settings/index', array(
                        'group' => $storeGroup->getId()
                    )),
                    'event' => $eventSent
                ));
            } catch (\Exception $e) {
                $this->nitro->disconnect($storeGroup->getCode());
                $errorMessage = $e->getMessage();
                return $this->resultJsonFactory->create()->setData(array(
                    'connected' => false,
                    'errors' => $errorMessage
                ));
            }
        }
    }
}

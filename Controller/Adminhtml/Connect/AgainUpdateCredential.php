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
use Magento\Framework\Controller\Result\JsonFactory;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\AdminFrontendUrl;
use NitroPack\NitroPack\Helper\NitroPackConfigHelper;
use Magento\Store\Model\StoreManagerInterface;
/**
 * Class AgainUpdateCredential - Again POST Controller to connect again to NitroPack
 * @extends Action
 * @package NitroPack\NitroPack\Controller\Adminhtml\Connect
 * @since 2.1.0
 */
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

    /**
     * @param Context $context
     * @param NitroServiceInterface $nitro
     * @param AdminFrontendUrl $urlHelper
     * @param NitroPackConfigHelper $_helper
     * @param StoreManagerInterface $storeManager
     * */
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

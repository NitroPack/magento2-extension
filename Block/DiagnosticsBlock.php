<?php

namespace NitroPack\NitroPack\Block;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\LogContentInterface;

class DiagnosticsBlock extends Template
{

    /**
     * @var UrlInterface
     * */
    protected $backendUrl;
    /**
     * @var StoreManagerInterface
     * */
    protected $storeManager;

    protected $logContent;

    public function __construct(
        Context               $context, // required as part of the Magento\Backend\Block\Template constructor
        UrlInterface          $backendUrl,
        StoreManagerInterface $storeManager, // dependency injection'ed
        LogContentInterface   $logContent,
        array                 $data = [] // required as part of the Magento\Backend\Block\Template constructor
    )
    {
        parent::__construct($context, $data);
        $this->_backendUrl = $backendUrl;
        $this->_storeManager = $storeManager;
        $this->logContent = $logContent;
    }

    public function getDiagnosticsReport()
    {
        $params = array();

        $params['group'] = $this->getStoreGroup()->getId();
        $params['form_key'] = $this->getFormKey();

        return $this->_backendUrl->getUrl('NitroPack/report/DiagnosticsReport', $params);
    }

    public function getStoreGroup()
    {
        $storeGroupId = (int)$this->_request->getParam('group', 0);
        if ($storeGroupId == 0) {
            $storeGroupId = $this->_storeManager->getGroup()->getId();
        }
        return $this->_storeManager->getGroup($storeGroupId);
    }

    /**
     * @return string
     */
    public function getLogContent(): string
    {
        return $this->logContent->getLogContent();
    }

    /**
     * @return string
     */
    public function getSendEmailUrl(): string
    {
        $params = [];
        $params['group'] = $this->getStoreGroup()->getId();
        $params['form_key'] = $this->getFormKey();

        return $this->_backendUrl->getUrl('NitroPack/report/SendLogReport', $params);
    }

    public function getDownloadLogUrl(): string
    {
        $params = [];
        $params['group'] = $this->getStoreGroup()->getId();
        $params['form_key'] = $this->getFormKey();

        return $this->_backendUrl->getUrl('NitroPack/report/DownloadLog', $params);
    }

    public function getDiagnosticsReportUrl(): string
    {
        $params = [];
        $params['group'] = $this->getStoreGroup()->getId();
        $params['form_key'] = $this->getFormKey();

        return $this->_backendUrl->getUrl('NitroPack/report/SendDiagnosticsReport', $params);
    }

}

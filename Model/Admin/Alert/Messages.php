<?php

namespace NitroPack\NitroPack\Model\Admin\Alert;


use Magento\Security\Model\ResourceModel\AdminSessionInfo\Collection;
use Magento\Backend\Model\UrlInterface;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Notification\MessageInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Messages implements MessageInterface
{
    /**
     * @var UrlInterface
     * */
    protected $backendUrl;
    /**
     * @var Collection
     * */
    private $adminSessionInfoCollection;
    /**
     * @var Session
     * */
    protected $authSession;
    /**
     * @var \NitroPack\NitroPack\Helper\NitroPackConfigHelper
     * */
    protected $nitroPackConfigHelper;
    /**
     * @var \NitroPack\NitroPack\Helper\InvalidationHelper
     * */
    protected $invalidationHelper;
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;
    /**
     * @var \NitroPack\NitroPack\Model\NitroPackNotification\Notifications
     * */
    protected $notifications;

    public function __construct(
        Collection $adminSessionInfoCollection,
        UrlInterface $backendUrl,
        \NitroPack\NitroPack\Helper\NitroPackConfigHelper $nitroPackConfigHelper,
        \NitroPack\NitroPack\Helper\InvalidationHelper $invalidationHelper,
        \NitroPack\NitroPack\Model\NitroPackNotification\Notifications $notifications,
        ScopeConfigInterface $_scopeConfig,
        Session $authSession
    ) {
        $this->authSession = $authSession;
        $this->backendUrl = $backendUrl;
        $this->nitroPackConfigHelper = $nitroPackConfigHelper;
        $this->invalidationHelper = $invalidationHelper;
        $this->_scopeConfig = $_scopeConfig;
        $this->notifications = $notifications;
        $this->adminSessionInfoCollection = $adminSessionInfoCollection;
    }

    public function getText()
    {
        $message = "";
        if (!$this->nitroPackConfigHelper->getFullPageCacheValue(
            ) || !empty($this->nitroPackConfigHelper->getDisabledCaches())) {
            if (!$this->nitroPackConfigHelper->getFullPageCacheValue(
                ) && !empty($this->nitroPackConfigHelper->getDisabledCaches())) {
                return __(
                    'Nitropack is disabled due to incompatible Cache setting. NitroPack requires to be set as %1 and %2 enabled',
                    '<a href="' . $this->backendUrl->getUrl(
                        'adminhtml/system_config/edit/section/system'
                    ) . '">Caching Application</a>',
                    '<a href="' . $this->backendUrl->getUrl('adminhtml/cache/index') . '">Full Page Caching</a>',
                );
            }
            if (!$this->nitroPackConfigHelper->getFullPageCacheValue()) {
                return __(
                    'Nitropack is disabled due to incompatible  %1. NitroPack must be selected as a Cache application',
                    '<a href="' . $this->backendUrl->getUrl(
                        'adminhtml/system_config/edit/section/system'
                    ) . '">Full Page Cache Application settings</a>'
                );
            }
            if (!empty($this->nitroPackConfigHelper->getDisabledCaches())) {
                return __(
                    'Nitropack is disabled due to incompatible  %1. NitroPack requires Full Page Caching enabled',
                    '<a href="' . $this->backendUrl->getUrl('adminhtml/cache/index') . '">Full Page Caching setting</a>'
                );
            }
        } else {
            foreach ($this->notifications->get('system') as $notification) {
                $message .= $notification['message'] . '<br>';
            }
        }
        return $message;
    }

    public function getIdentity()
    {
        return base64_encode('NITROPACK_NITROPACK' . $this->authSession->getUser()->getLogdate());
    }

    public function isDisplayed()
    {
        if($this->invalidationHelper->checkHavePreviouslyConnected()){
        if (!$this->nitroPackConfigHelper->getFullPageCacheValue(
            ) || !empty($this->nitroPackConfigHelper->getDisabledCaches())) {
            if (!$this->nitroPackConfigHelper->getFullPageCacheValue(
                ) && !empty($this->nitroPackConfigHelper->getDisabledCaches())) {
                return true;
            }
            if (!$this->nitroPackConfigHelper->getFullPageCacheValue()) {
                return true;
            }
            if (!empty($this->nitroPackConfigHelper->getDisabledCaches())) {
                return true;
            }
        } else {
            return count($this->notifications->get('system')) > 0 ? true : false;
        }
        }
        return false;
    }

    public function getSeverity()
    {
        if (!$this->nitroPackConfigHelper->getFullPageCacheValue(
            ) || !empty($this->nitroPackConfigHelper->getDisabledCaches())) {
            return \Magento\Framework\Notification\MessageInterface::SEVERITY_CRITICAL;
        } else {
            return \Magento\Framework\Notification\MessageInterface::SEVERITY_NOTICE;
        }
    }


}

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
namespace NitroPack\NitroPack\Model\Admin\Alert;


use Magento\Security\Model\ResourceModel\AdminSessionInfo\Collection;
use Magento\Backend\Model\UrlInterface;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Notification\MessageInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use NitroPack\NitroPack\Helper\FastlyHelper;

/**
 * Class Messages - Messages
 * @package NitroPack\NitroPack\Model\Admin\Alert
 * @since 2.0.0
 * */
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

    /**
     * @var FastlyHelper
     */
    protected $fastlyHelper;

    public function __construct(
        Collection                                                     $adminSessionInfoCollection,
        UrlInterface                                                   $backendUrl,
        \NitroPack\NitroPack\Helper\NitroPackConfigHelper              $nitroPackConfigHelper,
        \NitroPack\NitroPack\Helper\InvalidationHelper                 $invalidationHelper,
        \NitroPack\NitroPack\Model\NitroPackNotification\Notifications $notifications,
        ScopeConfigInterface                                           $_scopeConfig,
        Session                                                        $authSession,
        FastlyHelper                                                   $fastlyHelper
    )
    {
        $this->authSession = $authSession;
        $this->backendUrl = $backendUrl;
        $this->nitroPackConfigHelper = $nitroPackConfigHelper;
        $this->invalidationHelper = $invalidationHelper;
        $this->_scopeConfig = $_scopeConfig;
        $this->notifications = $notifications;
        $this->adminSessionInfoCollection = $adminSessionInfoCollection;
        $this->fastlyHelper = $fastlyHelper;
    }

    public function getText()
    {
        $message = "";
        if (!$this->nitroPackConfigHelper->getFullPageCacheValue() || !empty($this->nitroPackConfigHelper->getDisabledCaches())) {
            if (!$this->nitroPackConfigHelper->getFullPageCacheValue() && !empty($this->nitroPackConfigHelper->getDisabledCaches())) {

                return __(
                    'Nitropack is currently disabled because it\'s not selected in the %1 and Enable %2 ',
                    '<a href="' . $this->backendUrl->getUrl(
                        'adminhtml/system_config/edit/section/system'
                    ) . '">Full Page Cache Application settings</a>',
                    '<a href="' . $this->backendUrl->getUrl('adminhtml/cache/index') . '">Full Page Caching</a>',
                );
            }
            if (!$this->nitroPackConfigHelper->getFullPageCacheValue()) {
                if (!$this->invalidationHelper->checkInvalidationAndPurgeProcess() && !$this->invalidationHelper->checkCronJobIsSetup()) {
                    return __(
                        'NitroPack has been disabled and the initial system settings have been restored due to incompatible Cron settings preventing cache invalidation/purge. Run the following command to fix the problem: php bin/magento queue:consumers:start nitropack.cache.queue.consumer & '
                    );
                }
                return __(
                    'NitroPack is currently disabled because it\'s not selected in the   %1. To resolve the problem, please choose NitroPack as your cache application',
                    '<a href="' . $this->backendUrl->getUrl(
                        'adminhtml/system_config/edit/section/system'
                    ) . '">Full Page Cache Application settings</a>'
                );
            }


            if (!empty($this->nitroPackConfigHelper->getDisabledCaches())) {
                return __(
                    'Nitropack is currently disabled due to incompatible %1. To resolve the problem, please enable Full page caching',
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
        if (!$this->nitroPackConfigHelper->getFullPageCacheValue() || !empty($this->nitroPackConfigHelper->getDisabledCaches())) {
            if (!$this->nitroPackConfigHelper->getFullPageCacheValue() && !empty($this->nitroPackConfigHelper->getDisabledCaches())) {
                return true;
            }
            if (!$this->nitroPackConfigHelper->getFullPageCacheValue() && !$this->fastlyHelper->isFastlyAndNitroPackEnabled()) {
                return true;
            }
            if (!empty($this->nitroPackConfigHelper->getDisabledCaches())) {
                return true;
            }
        } else {
            if ($this->invalidationHelper->checkHavePreviouslyConnected()) {
                return count($this->notifications->get('system')) > 0 ? true : false;
            }
        }
        return false;
    }

    public function getSeverity()
    {
        if (!$this->nitroPackConfigHelper->getFullPageCacheValue() || !empty($this->nitroPackConfigHelper->getDisabledCaches())) {
            return \Magento\Framework\Notification\MessageInterface::SEVERITY_CRITICAL;
        } else {
            return \Magento\Framework\Notification\MessageInterface::SEVERITY_NOTICE;
        }
    }


}

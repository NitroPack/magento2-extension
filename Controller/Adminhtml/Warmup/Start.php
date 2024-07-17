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
namespace NitroPack\NitroPack\Controller\Adminhtml\Warmup;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

use Magento\Store\Model\Store;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\SitemapHelper;
use NitroPack\NitroPack\Logger\Logger;

/**
 * Class Start - Controller start to start the warmup process
 * @extends StoreAwareAction
 * @package NitroPack\NitroPack\Controller\Adminhtml\Warmup
 * @since 2.0.0
 */
class Start extends StoreAwareAction
{
    /**
     * @var JsonFactory
     * */
    protected $resultJsonFactory;
    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;

    /**
     * @var SitemapHelper
     */
    protected $sitemapHelper;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Context $context
     * @param NitroServiceInterface $nitro
     * @param SitemapHelper $sitemapHelper
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        NitroServiceInterface $nitro,
        SitemapHelper $sitemapHelper,
        Logger $logger
    ) {
        parent::__construct($context, $nitro);
        $this->resultJsonFactory = $this->_objectManager->create(JsonFactory::class);
        $this->nitro = $nitro;
        $this->sitemapHelper = $sitemapHelper;
        $this->logger = $logger;
    }

    protected function nitroExecute()
    {

        try {
            $result = false;
            $permissionsIssue = false;
            $stats = $this->nitro->getApi()->getWarmupStats();
            $this->nitro->getApi()->enableWarmup();
            $storeGroup = $this->getStoreGroup();
            $sitemapUrl = $this->sitemapHelper->getSiteMapPath($storeGroup->getId(), $storeGroup->getCode(), $this->nitro->getSettings());
            if ($stats['pending'] == 0) {
                if ($sitemapUrl) {
                    $this->nitro->getApi()->runWarmup();
                    $result = true;
                } else {
                    $permissionsIssue = true;
                }
            }
            $this->nitro->getApi()->setWarmupHomepage($this->getStoreUrl());
            $this->nitro->getApi()->setWarmupSitemap($sitemapUrl);

        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this->resultJsonFactory->create()->setData(array(
            'success' => $result,
            'permissionsIssue' => $permissionsIssue ?? false
        ));
    }

    public function getStoreUrl()
    {
        $url = '';
        $store = $this->_objectManager->get(Store::class);
        $defaultStoreView = $this->storeManager->getStore($this->storeGroup->getDefaultStoreId());
        $url = $store->isUseStoreInUrl() ? str_replace(
            $defaultStoreView->getCode() . '/',
            '',
            $defaultStoreView->getBaseUrl()
        ) : $defaultStoreView->getBaseUrl(); // get store view name
        return $url;
    }
}

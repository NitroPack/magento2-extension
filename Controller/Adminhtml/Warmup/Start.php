<?php

namespace NitroPack\NitroPack\Controller\Adminhtml\Warmup;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\SitemapHelper;
use NitroPack\NitroPack\Logger\Logger;

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

        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this->resultJsonFactory->create()->setData(array(
            'success' => $result,
            'permissionsIssue' => $permissionsIssue ?? false
        ));
    }
}

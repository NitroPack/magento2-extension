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
namespace NitroPack\NitroPack\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Result\PageFactory;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Api\NitroServiceInterface;

/**
 * Class Index - Settings Controller for dashboard render and settings update
 * @extends StoreAwareAction
 * @package NitroPack\NitroPack\Controller\Adminhtml\Settings
 * @since 2.0.0
 */
class Index extends StoreAwareAction
{
    const USE_STORE_CODE = "groups/url/fields/use_store";
    /**
     * @var  PageFactory
     * */
    protected $resultPageFactory;
    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;

    /**
     * @var \NitroPack\NitroPack\Model\Dashboard\OptimizationMode
     * */
    protected $optimizationModeModel;

    /**
     * @var \NitroPack\NitroPack\Model\Dashboard\OptimizationPage
     * */
    protected $optimizationPageModel;

    /**
     * @var \NitroPack\NitroPack\Model\Dashboard\SubscriptionBlock
     * */
    protected $subscriptionBlock;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ScopeConfigInterface $_scopeConfig
     * @param \NitroPack\NitroPack\Model\Dashboard\OptimizationMode $optimizationModeModel
     * @param \NitroPack\NitroPack\Model\Dashboard\OptimizationPage $optimizationPageModel
     * @param \NitroPack\NitroPack\Model\Dashboard\SubscriptionBlock $subscriptionBlock
     * @param NitroServiceInterface $nitro
     * */
    public function __construct(
        Context                                                $context,
        PageFactory                                            $resultPageFactory,
        ScopeConfigInterface                                   $_scopeConfig,
        \NitroPack\NitroPack\Model\Dashboard\OptimizationMode  $optimizationModeModel,
        \NitroPack\NitroPack\Model\Dashboard\OptimizationPage  $optimizationPageModel,
        \NitroPack\NitroPack\Model\Dashboard\SubscriptionBlock $subscriptionBlock,
        NitroServiceInterface                                  $nitro


    )
    {
        parent::__construct($context, $nitro);
        $this->resultPageFactory = $resultPageFactory;
        $this->nitro = $nitro;
        $this->optimizationPageModel = $optimizationPageModel;
        $this->optimizationModeModel = $optimizationModeModel;
        $this->subscriptionBlock = $subscriptionBlock;
        $this->_scopeConfig = $_scopeConfig;
    }

    protected function nitroExecute()
    {

        $this->optimizationModeModel->setNitro($this->nitro);
        $this->optimizationPageModel->setNitro($this->nitro);
        $this->subscriptionBlock->setNitro($this->nitro);
        if (!$this->nitro->isConnected()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($this->getUrlWithStore('NitroPack/connect/index'));
            return $resultRedirect;
        }

        try {
            $warmupStats = $this->nitro->getSdk()->getApi()->warmup->stats();
            if ((bool)$this->nitro->getSettings()->cacheWarmup != (bool)$warmupStats['status']) {
                $this->nitro->getSettings()->cacheWarmup = (bool)$warmupStats['status'];
                $this->nitro->persistSettings();
            }
        } catch (\Exception $exception) {

        }
        return $this->resultPageFactory->create();
    }


}

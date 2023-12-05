<?php

namespace NitroPack\NitroPack\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Api\NitroServiceInterface;

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
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ScopeConfigInterface $_scopeConfig
     * @param ObjectManagerInterface $objectManager
     * @param NitroServiceInterface $nitro
     * */
    public function __construct(
        Context                $context,
        PageFactory            $resultPageFactory,
        ScopeConfigInterface   $_scopeConfig,
        ObjectManagerInterface $objectManager,
        NitroServiceInterface  $nitro
    )
    {
        parent::__construct($context, $nitro);
        $this->resultPageFactory = $resultPageFactory;
        $this->nitro = $nitro;
        $this->objectManager = $objectManager;
        $this->_scopeConfig = $_scopeConfig;
    }

    protected function nitroExecute()
    {

        if (!$this->nitro->isConnected()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($this->getUrlWithStore('NitroPack/connect/index'));
            return $resultRedirect;
        }

        try{
        $warmupStats = $this->nitro->getSdk()->getApi()->warmup->stats();
        if((bool)$this->nitro->getSettings()->cacheWarmup != (bool)$warmupStats['status']){
            $this->nitro->getSettings()->cacheWarmup= (bool)$warmupStats['status'];
            $this->nitro->persistSettings();
        }
        }catch (\Exception $exception){

        }


       return $this->resultPageFactory->create();
    }
}

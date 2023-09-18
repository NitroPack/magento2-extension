<?php

namespace NitroPack\NitroPack\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Helper\NitroPackConfigHelper;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use NitroPack\NitroPack\Helper\VarnishHelper;

class EnableSafeMode extends StoreAwareAction
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
     * @var NitroPackConfigHelper
     * */
    protected $_helper;
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;
    /**
     * @var FormKeyValidator
     * */
    protected $formKeyValidator;
    /**
     * @var VarnishHelper
     * */
    protected $varnishHelper;
    /**
     * @param Context $context
     * @param NitroServiceInterface $nitro
     * @param NitroPackConfigHelper $_helper
     * @param ScopeConfigInterface $_scopeConfig
     * @param FormKeyValidator $formKeyValidator
     * @param VarnishHelper $varnishHelper
     * */
    public function __construct(
        Context               $context,
        NitroServiceInterface $nitro,
        NitroPackConfigHelper $_helper,
        ScopeConfigInterface  $_scopeConfig,
        FormKeyValidator      $formKeyValidator,
        VarnishHelper $varnishHelper
    )
    {
        parent::__construct($context, $nitro);
        $this->resultJsonFactory = $this->_objectManager->create(JsonFactory::class);
        $this->nitro = $nitro;
        $this->_scopeConfig = $_scopeConfig;
        $this->varnishHelper = $varnishHelper;
        $this->_helper = $_helper;
        $this->formKeyValidator = $formKeyValidator;

    }

    public function nitroExecute()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            throw new LocalizedException(__('Invalid form key.'));
        }

        if ($this->_request->getPostValue('action')) {
           $enabled  =  $this->_request->getPostValue('action') == 'nitropack_enable_safemode' ? true : false;
            $setting = $this->nitro->getSettings();
            try {
                if (
                    !is_null($this->_scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK))
                    && $this->_scopeConfig->getValue(
                        NitroService::FULL_PAGE_CACHE_NITROPACK
                    ) == NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE
                    &&   !is_null($this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_BACKEND_HOST))
                    && !is_null($this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_NITRO_ENABLED))
                    && $this->_scopeConfig->getValue(NitroService::XML_VARNISH_PAGECACHE_NITRO_ENABLED)
                ) {
                    $this->varnishHelper->purgeVarnish();
                }
                if($enabled){
                $this->nitro->getSdk()->enableSafeMode();
                }else{
                $this->nitro->getSdk()->disableSafeMode();
                }
                $setting->safeMode = $enabled;
                $this->nitro->persistSettings();
            } catch (\Exception $e) {

            }
            return $this->resultJsonFactory->create()->setData(array(
                'safe_mode' => $enabled,

            ));
        }

        // TODO: Implement nitroExecute() method.
    }
}

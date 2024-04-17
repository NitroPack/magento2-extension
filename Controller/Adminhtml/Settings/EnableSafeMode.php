<?php

namespace NitroPack\NitroPack\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Helper\NitroPackConfigHelper;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use NitroPack\NitroPack\Model\FullPageCache\PurgeInterface;

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
     * @var FormKeyValidator
     * */
    protected $formKeyValidator;
    /**
     * @var PurgeInterface
     * */
    protected $purgeInterface;

    /**
     * @param Context $context
     * @param NitroServiceInterface $nitro
     * @param NitroPackConfigHelper $_helper
     * @param FormKeyValidator $formKeyValidator
     * @param PurgeInterface $purgeInterface
     * */
    public function __construct(
        Context               $context,
        NitroServiceInterface $nitro,
        NitroPackConfigHelper $_helper,
        FormKeyValidator      $formKeyValidator,
        PurgeInterface        $purgeInterface
    )
    {
        parent::__construct($context, $nitro);
        $this->resultJsonFactory = $this->_objectManager->create(JsonFactory::class);
        $this->nitro = $nitro;
        $this->purgeInterface = $purgeInterface;
        $this->_helper = $_helper;
        $this->formKeyValidator = $formKeyValidator;

    }

    public function nitroExecute()
    {

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            throw new LocalizedException(__('Invalid form key.'));
        }

        if ($this->_request->getPostValue('action')) {
            $enabled = $this->_request->getPostValue('action') == 'nitropack_enable_safemode' ? true : false;
            $setting = $this->nitro->getSettings();
            try {

                //$this->purgeInterface->purge();

                if ($enabled) {
                    $this->nitro->getSdk()->enableSafeMode();
                } else {
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

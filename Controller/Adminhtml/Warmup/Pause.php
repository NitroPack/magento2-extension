<?php

namespace NitroPack\NitroPack\Controller\Adminhtml\Warmup;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Api\NitroServiceInterface;

class Pause extends StoreAwareAction
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
     * @param Context $context
     * @param NitroServiceInterface $nitro
     * */
    public function __construct(
        Context $context,
        NitroServiceInterface $nitro
    ) {
        parent::__construct($context, $nitro);
        $this->resultJsonFactory = $this->_objectManager->create(JsonFactory::class);
        $this->nitro = $nitro;
    }

    protected function nitroExecute()
    {
        try {
            $this->nitro->getApi()->disableWarmup();
            return $this->resultJsonFactory->create()->setData(array(
                'paused' => true
            ));
        } catch (\Exception $e) {
            return $this->resultJsonFactory->create()->setData(array(
                'paused' => false
            ));
        }
    }
}

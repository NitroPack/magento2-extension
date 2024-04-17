<?php

namespace NitroPack\NitroPack\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Api\NitroServiceInterface;


class Disconnect extends StoreAwareAction
{
    /**
     * @var JsonFactory
     * */
    protected $resultJsonFactory;

    protected $storeGroup;
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

        $this->storeGroup = $this->getStoreGroup();
    }

    protected function nitroExecute()
    {
        try {
            $this->nitro->getApi()->disableWarmup();
            $this->nitro->getApi()->resetWarmup();
            $this->nitro->disconnect($this->getStoreGroup()->getCode());
            $this->nitro->getApi()->unsetWebhook("config");
            $this->nitro->getApi()->unsetWebhook("cache_clear");
            $this->nitro->getApi()->unsetWebhook("cache_ready");
            $this->nitro->getSdk()->disableSafeMode();
            $eventUrl = $this->nitro->integrationUrl('extensionEvent');
            $eventSent = $this->nitro->nitroEvent('disconnect', $eventUrl, $this->storeGroup);
            $this->nitro->purgeLocalCache(true);

            return $this->resultJsonFactory->create()->setData(array(
                'disconnected' => true,
                'event' => $eventSent
            ));
        } catch (\Exception $e) {
            return $this->resultJsonFactory->create()->setData(array(
                'disconnected' => false,
                'info' => 'Exception occured while disconnecting: ' . $e->getMessage()
            ));
        }
    }
}

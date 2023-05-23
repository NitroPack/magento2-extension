<?php

namespace NitroPack\NitroPack\Controller\Webhook;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;

use NitroPack\NitroPack\Api\NitroServiceInterface;

abstract class WebhookController extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;
    /**
     * @var RawFactory
     * */
    protected $responseFactory;

    /**
     * @param Context $context
     * */
    public function __construct(Context $context)
    {

        parent::__construct($context);
        $objManager = $context->getObjectManager();
        $this->nitro = $objManager->get(NitroServiceInterface::class);

        if(!$this->isValidNitropackWebhook()){
            throw new \Exception('Invalid WebHook token ');
        }
        $this->responseFactory = $objManager->get(RawFactory::class);
    }

    protected function textResponse($contents)
    {
        $result = $this->responseFactory->create();
        $result->setHeader('Content-Type', 'text/html');
        $result->setContents($contents);
        return $result;
    }


    function isValidNitropackWebhook() {
        return !empty($this->_request->getParam("token")) && $this->nitropackValidateWebhookToken($this->_request->getParam("token"));
    }


    function nitropackValidateWebhookToken($token){
        $data = $this->nitro->getSettings();
        return preg_match("/^([abcdef0-9]{32})$/", strtolower($token)) && $token == $this->nitro->nitroGenerateWebhookToken($data->siteId);
    }

}

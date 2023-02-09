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
        $this->responseFactory = $objManager->get(RawFactory::class);
    }

    protected function textResponse($contents)
    {
        $result = $this->responseFactory->create();
        $result->setHeader('Content-Type', 'text/html');
        $result->setContents($contents);
        return $result;
    }

}

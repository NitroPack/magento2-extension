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
namespace NitroPack\NitroPack\Controller\Webhook;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;

use NitroPack\NitroPack\Api\NitroServiceInterface;
/**
 * Class WebhookController - abstract class for NitroPack Webhook controllers
 * @extends \Magento\Framework\App\Action\Action
 * @implements HttpGetActionInterface,HttpPostActionInterface
 * @abstract
 * @package NitroPack\NitroPack\Controller\Webhook
 * @since 2.0.0
 */
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
        return  $token == $this->nitro->nitroGenerateWebhookToken($data->siteId);
    }

}

<?php

namespace NitroPack\NitroPack\Controller\Purge;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use NitroPack\NitroPack\Helper\VarnishHelper;

class Index extends Action
{
    protected $_shell;
    /**
     * @var RequestInterface
     * */
    protected $request;
    /**
     * @var VarnishHelper
     * */
    protected $varnishHelper;

    protected $resultJsonFactory;

    public function __construct(
        Context $context,
        VarnishHelper $varnishHelper,
        RequestInterface $request
    ) {
        $this->request = $request;
        $this->varnishHelper = $varnishHelper;
        $this->resultJsonFactory = $this->_objectManager->create(JsonFactory::class);
        parent::__construct($context);
    }

    public function execute()
    {
        $this->varnishHelper->purgeVarnish();
        $resultData = ['message' => 'Successfully purge'];
        return $this->resultJsonFactory->create()->setData($resultData);
    }
}

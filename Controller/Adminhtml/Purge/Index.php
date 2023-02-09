<?php

namespace NitroPack\NitroPack\Controller\Adminhtml\Purge;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use NitroPack\NitroPack\Helper\VarnishHelper;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{

    /**
     * @var RequestInterface
     * */
    protected $request;
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     * */
    protected $resultPageFactory;
    /**
     * @var VarnishHelper
     * */
    protected $varnishHelper;
    /**
     * @param  Context $context
     * @param  VarnishHelper $varnishHelper,
     * @param  PageFactory $resultPageFactory,
     * @param  RequestInterface $request
     * */
    public function __construct(
        Context $context,
        VarnishHelper $varnishHelper,
        PageFactory $resultPageFactory,
        RequestInterface $request
    ) {
        $this->request = $request;
        $this->varnishHelper = $varnishHelper;
        $this->resultPageFactory = $resultPageFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $this->varnishHelper->purgeVarnish();
        return $this->resultPageFactory->create();
    }
}

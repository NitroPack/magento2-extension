<?php

namespace NitroPack\NitroPack\Controller\Adminhtml\Purge;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;
use NitroPack\NitroPack\Model\FullPageCache\PurgeInterface;

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
     * @var PurgeInterface
     * */
    protected $purgeInterface;
    /**
     * @param  Context $context
     * @param  PurgeInterface $purgeInterface
     * @param  PageFactory $resultPageFactory
     * @param  RequestInterface $request
     * */
    public function __construct(
        Context $context,
        PurgeInterface $purgeInterface,
        PageFactory $resultPageFactory,
        RequestInterface $request
    ) {
        $this->request = $request;
        $this->purgeInterface = $purgeInterface;
        $this->resultPageFactory = $resultPageFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $this->purgeInterface->purge();
        return $this->resultPageFactory->create();
    }
}

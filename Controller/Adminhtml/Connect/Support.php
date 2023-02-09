<?php

namespace NitroPack\NitroPack\Controller\Adminhtml\Connect;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\AdminFrontendUrl;

class Support extends Action
{
    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;
    /**
     * @var PageFactory
     * */
    protected $resultPageFactory;
    /**
     * @var AdminFrontendUrl
     * */
    protected $urlHelper;
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        return $this->resultPageFactory->create();
    }
}

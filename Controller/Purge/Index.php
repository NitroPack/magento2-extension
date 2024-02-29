<?php

namespace NitroPack\NitroPack\Controller\Purge;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use NitroPack\NitroPack\Model\FullPageCache\PurgeInterface;

class Index extends Action
{

    /**
     * @var RequestInterface
     * */
    protected $request;
    /**
     * @var PurgeInterface
     * */
    protected $purgeInterface;
    /**
     * @var JsonFactory
     * */
    protected $resultJsonFactory;
    /**
     * @param Context $context
     * @param PurgeInterface $purgeInterface
     * @param RequestInterface $request
     * */
    public function __construct(
        Context $context,
        PurgeInterface $purgeInterface,
        RequestInterface $request
    ) {
        $this->request = $request;
        $this->purgeInterface = $purgeInterface;
        $this->resultJsonFactory = $this->_objectManager->create(JsonFactory::class);
        parent::__construct($context);
    }

    public function execute()
    {
        $this->purgeInterface->purge();
        $resultData = ['message' => 'Successfully purge'];
        return $this->resultJsonFactory->create()->setData($resultData);
    }
}

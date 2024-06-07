<?php

namespace NitroPack\NitroPack\Controller\Adminhtml\System;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\FlagManager;
use Magento\Framework\Message\ManagerInterface;

class Dismiss extends Action
{

    /**
     * @var FlagManager
     */
    private $flagManager;
    /**
     * @var JsonFactory
     */

    protected $resultJsonFactory;
    /**
     * @var ManagerInterface
     * */
    protected $messageManager;

    public function __construct(
        Context          $context,
        JsonFactory      $resultJsonFactory,
        ManagerInterface $messageManager,
        FlagManager      $flagManager
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->messageManager = $messageManager;
        $this->flagManager = $flagManager;
    }

    public function execute()
    {
        $dismissedMessages = $this->flagManager->getFlagData('nitropack_varnish_mismtach_messages') ?? [];
        array_push($dismissedMessages, $this->getRequest()->getParam('message_code'));
        $this->flagManager->saveFlag('nitro_varnish_mismatch_message', array_unique($dismissedMessages));
        return $this->resultJsonFactory->create()->setData(array(
            'dismiss' => true,
        ));
    }
}

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
namespace NitroPack\NitroPack\Controller\Adminhtml\System;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\FlagManager;
use Magento\Framework\Message\ManagerInterface;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;

/**
 * Class Dismiss - Controller Dismiss to dismiss the warning or error messages from dashboard page
 * @extends StoreAwareAction
 * @package NitroPack\NitroPack\Controller\Adminhtml\System
 * @since 3.2.0
 */
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
        $flag = $this->getRequest()->getParam('flag');
        $dismissedMessages = $this->flagManager->getFlagData($flag) ?? [];
        array_push($dismissedMessages, $this->getRequest()->getParam('message_code'));
        $this->flagManager->saveFlag($flag, array_unique($dismissedMessages));
        return $this->resultJsonFactory->create()->setData(array(
            'dismiss' => true,
        ));
    }
}

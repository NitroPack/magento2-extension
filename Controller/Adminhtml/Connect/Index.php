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

namespace NitroPack\NitroPack\Controller\Adminhtml\Connect;


use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Api\NitroServiceInterface;

/**
 * Class Index - Connect Page Render When NitroPack is not connected
 * @extends StoreAwareAction
 * @package NitroPack\NitroPack\Controller\Adminhtml\Connect
 * @since 2.0.0
 */
class Index extends StoreAwareAction
{
    /**
     * @var PageFactory $resultPageFactory
     * */
    protected $resultPageFactory;
    /**
     * @var NitroServiceInterface $nitro
     * */
    protected $nitro;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param NitroServiceInterface $nitro
     * */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        NitroServiceInterface $nitro
    ) {
        parent::__construct($context, $nitro);
        $this->resultPageFactory = $resultPageFactory;
        $this->nitro = $nitro;
    }

    protected function nitroExecute()
    {
        if ($this->nitro->isConnected()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($this->getUrlWithStore('NitroPack/settings/index'));
            return $resultRedirect;
        }

        return $this->resultPageFactory->create();
    }
}

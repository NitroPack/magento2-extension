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
namespace NitroPack\NitroPack\Controller\Purge;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use NitroPack\NitroPack\Model\FullPageCache\PurgeInterface;
/**
 * Class Index - Controller Index to purge the cache
 * @extends \Magento\Backend\App\Action
 * @package NitroPack\NitroPack\Controller\Purge
 * @since 2.0.0
 */
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

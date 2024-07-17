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

namespace NitroPack\NitroPack\Controller\Adminhtml\Report;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use Magento\Framework\Controller\Result\RawFactory;
use NitroPack\NitroPack\Api\LogContentInterface;

/**
 * Class DownloadLog - DownloadLog Controller to download the NitroPack log
 * @extends StoreAwareAction
 * @package NitroPack\NitroPack\Controller\Adminhtml\Cache
 * @since 3.2.0
 */
class DownloadLog extends StoreAwareAction
{
    /**
     * @var RawFactory
     */
    private $resultRawFactory;

    /**
     * @var LogContentInterface
     */
    private $logContent;

    /**
     * @param Context $context
     * @param NitroServiceInterface $nitro
     * @param RawFactory $resultRawFactory
     * @param LogContentInterface $logContent
     */
    public function __construct(
        Context               $context,
        NitroServiceInterface $nitro,
        RawFactory            $resultRawFactory,
        LogContentInterface   $logContent
    )
    {
        parent::__construct($context, $nitro);
        $this->resultRawFactory = $resultRawFactory;
        $this->logContent = $logContent;
    }

    /**
     * @return Raw
     */
    public function nitroExecute()
    {
        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setHttpResponseCode(200);
        $resultRaw->setHeader('Content-Type', 'application/json', true);
        $resultRaw->setHeader('Content-Disposition', 'attachment; filename=' . 'nitropack_log.txt');
        $resultRaw->setContents($this->logContent->getLogContent(true));

        return $resultRaw;
    }
}

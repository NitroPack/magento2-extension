<?php
/**
 * @author NitroPack Inc.
 * @copyright Copyright (c) NitroPack Inc. (https://www.nitropack.io)
 * @package NitroPack for Magento 2
 */

namespace NitroPack\NitroPack\Controller\Adminhtml\Report;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use Magento\Framework\Controller\Result\RawFactory;
use NitroPack\NitroPack\Api\LogContentInterface;

/**
 * class DownloadLog
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

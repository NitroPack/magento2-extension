<?php
/**
 * @author NitroPack Inc.
 * @copyright Copyright (c) NitroPack Inc. (https://www.nitropack.io)
 * @package NitroPack for Magento 2
 */

namespace NitroPack\NitroPack\Controller\Adminhtml\Report;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Message\ManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Serialize\SerializerInterface;
use NitroPack\NitroPack\Api\LogContentInterface;
use Magento\Framework\Controller\Result\JsonFactory;



class DownloadLog extends StoreAwareAction
{
    /**
     * @var
     */
    private $request;

    /**
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * @var null
     */
    private $configSetting = null;

    /**
     * @var RawFactory
     */
    private $resultRawFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var SendEmailInterface
     */
    private $logContent;

    private $jsonFactory;


    /**
     * @param Context $context
     * @param ScopeConfigInterface $_scopeConfig
     * @param NitroServiceInterface $nitro
     * @param RawFactory $resultRawFactory
     * @param ManagerInterface $messageManager
     * @param SerializerInterface $serializer
     * @param LogContentInterface $logContent
     */
    public function __construct(
        Context                                     $context,
        ScopeConfigInterface                        $_scopeConfig,
        NitroServiceInterface                       $nitro,
        RawFactory                                  $resultRawFactory,
        ManagerInterface $messageManager,
        SerializerInterface                         $serializer,
        LogContentInterface $logContent,
        JsonFactory $jsonFactory

    )
    {
        parent::__construct($context, $nitro);
        $this->resultRawFactory = $resultRawFactory;
        $this->serializer = $serializer;
        $this->_scopeConfig = $_scopeConfig;
        $this->logContent = $logContent;
        $this->messageManager = $messageManager;
        $this->jsonFactory = $jsonFactory;

    }

    /**
     * @return Raw
     */
    public function nitroExecute()
    {
        if (!is_null($this->nitro)) {
            $this->configSetting = (array)$this->nitro->getSettings();
        }

        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setHttpResponseCode(200);
        $resultRaw->setHeader('Content-Type', 'application/json', true);
        $resultRaw->setHeader('Content-Disposition', 'attachment; filename=' . 'nitropack_log.txt');
        $resultRaw->setContents($this->logContent->getLogContent(true));

        return $resultRaw;
    }
}

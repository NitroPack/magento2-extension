<?php
/**
 * @author NitroPack Inc.
 * @copyright Copyright (c) NitroPack Inc. (https://www.nitropack.io)
 * @package NitroPack for Magento 2
 */

namespace NitroPack\NitroPack\Controller\Adminhtml\Report;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Message\ManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Serialize\SerializerInterface;
use NitroPack\NitroPack\Api\SendEmailInterface;
use NitroPack\NitroPack\Api\LogContentInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Laminas\Mime\Mime;

class SendLogReport extends StoreAwareAction
{

    private const LOG_REPORT_EMAIL_CONTENT = 'Hello,<br>Attached you can find the NitroPack log for {{url}} <br><br>Regards,<br>NitroPack Team';
    private const LOG_REPORT_EMAIL_SUBJECT = 'Log Report';
    private const LOG_REPORT_EMAIL_ATTACHMENT_FILENAME = 'NitroPackLog.log';
    private const LOG_REPORT_EMAIL_ATTACHMENT_FILETYPE = Mime::TYPE_TEXT;

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
    private $sendEmailInterface;

    /**
     * @var LogContentInterface
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
     * @param SendEmailInterface $sendEmailInterface
     */
    public function __construct(
        Context               $context,
        ScopeConfigInterface  $_scopeConfig,
        NitroServiceInterface $nitro,
        RawFactory            $resultRawFactory,
        ManagerInterface      $messageManager,
        SerializerInterface   $serializer,
        SendEmailInterface    $sendEmailInterface,
        LogContentInterface   $logContent,
        JsonFactory           $jsonFactory
    )
    {
        parent::__construct($context, $nitro);
        $this->resultRawFactory = $resultRawFactory;
        $this->serializer = $serializer;
        $this->_scopeConfig = $_scopeConfig;
        $this->sendEmailInterface = $sendEmailInterface;
        $this->messageManager = $messageManager;
        $this->logContent = $logContent;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * @return Json
     */
    public function nitroExecute()
    {
        $result = false;

        if (!is_null($this->nitro)) {
            $this->configSetting = (array)$this->nitro->getSettings();
        }

        $toEmail = $this->_request->getParam('email') ?? null;

        if ($toEmail) {
            try {
                $data['content'] = self::LOG_REPORT_EMAIL_CONTENT;
                $data['subject'] = self::LOG_REPORT_EMAIL_SUBJECT . ' : ' . $this->getStoreUrl();
                $data['attachment'] = [
                    'content' => $this->logContent->getLogContent(true),
                    'filename' => self::LOG_REPORT_EMAIL_ATTACHMENT_FILENAME,
                    'filetype' => self::LOG_REPORT_EMAIL_ATTACHMENT_FILETYPE
                ];

                $this->sendEmailInterface->send($toEmail, $data);

                $result = true;

            } catch (Exception $exception) {

            }
        }

        return $this->jsonFactory->create()->setData(['result' => $result]);
    }

    /**
     * @return string
     */
    private function getStoreUrl(): string
    {
        try {
            $storeUrl = $this->storeManager->getStore()->getBaseUrl();
        } catch (Exception $exception) {
            $storeUrl = '';
        }
        return $storeUrl;
    }
}

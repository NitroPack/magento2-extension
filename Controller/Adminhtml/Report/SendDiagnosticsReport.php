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

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Helper\DiagnosticsReport as DiagnosticsReportHelper;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Filesystem\Io\File;
use NitroPack\NitroPack\Api\SendEmailInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class SendDiagnosticsReport - Send Email Diagnostics Report Controller
 * @extends StoreAwareAction
 * @package NitroPack\NitroPack\Controller\Adminhtml\Cache
 * @since 3.2.0
 */

class SendDiagnosticsReport extends StoreAwareAction
{
    private const DIAGNOSTICS_REPORT_EMAIL_CONTENT = 'Hello,<br>Attached you can find the diagnostics report for {{url}} <br><br>Regards,<br>NitroPack Team';
    private const DIAGNOSTICS_REPORT_EMAIL_SUBJECT = 'Diagnostics Report';
    private const DIAGNOSTICS_REPORT_EMAIL_ATTACHMENT_FILENAME = 'DiagnosticsReport.json';
    private const DIAGNOSTICS_REPORT_EMAIL_ATTACHMENT_FILETYPE = 'application/json';

    /**
     * @var DiagnosticsReportHelper
     * */
    private $diagnosticsReportHelper;
    /**
     * @var RequestInterface
     * */
    protected $request;
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;

    public $configSetting = null;
    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var RawFactory
     */
    private $resultRawFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var SendEmailInterface
     */
    private $sendEmailInterface;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;


    /**
     * @param Context $context
     * @param ScopeConfigInterface $_scopeConfig
     * @param NitroServiceInterface $nitro
     * @param DiagnosticsReportHelper $diagnosticsReportHelper
     * @param FileFactory $fileFactory
     * @param RawFactory $resultRawFactory
     * @param Filesystem $filesystem
     * @param ManagerInterface $messageManager
     * @param SerializerInterface $serializer
     * @param File $file
     * @param SendEmailInterface $sendEmailInterface
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context                 $context,
        ScopeConfigInterface    $_scopeConfig,
        NitroServiceInterface   $nitro,
        DiagnosticsReportHelper $diagnosticsReportHelper,
        FileFactory $fileFactory,
        RawFactory $resultRawFactory,
        Filesystem $filesystem,
        ManagerInterface $messageManager,
        SerializerInterface $serializer,
        File $file,
        SendEmailInterface $sendEmailInterface,
        JsonFactory $jsonFactory
    ) {


        parent::__construct($context, $nitro);
        $this->file = $file;
        $this->fileFactory = $fileFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->filesystem = $filesystem;
        $this->serializer = $serializer;
        $this->diagnosticsReportHelper = $diagnosticsReportHelper;
        $this->_scopeConfig = $_scopeConfig;
        $this->sendEmailInterface = $sendEmailInterface;
        $this->jsonFactory = $jsonFactory;
    }

    public function nitroExecute()
    {

        $result = false;

        if (!is_null($this->nitro)) {
            $this->configSetting = (array) $this->nitro->getSettings();
        }

        $email = $this->getRequest()->getParam('email');

        $nitroDiagnosticFunctions = array(
            'general-info-status' => 'getGeneralInfo',
            'active-plugins-status' => 'getActivePlugins',
            'conflicting-plugins-status' => 'getConflictingPlugins',
            'user-config-status' => 'getUserConfig',
            'dir-info-status' => 'getDirInfo',
        );

        $diag_data=[];
        try {
            $ar = !empty($this->_request->getParam("toggled")) ? $this->_request->getParam("toggled") : NULL;
            $errorMsg = [];
            if ($ar !== NULL) {

                foreach ($ar as $func_name => $func_allowed) {
                    if ((boolean)$func_allowed) {
                        try{
                            $diag_data[$func_name] =$this->{$nitroDiagnosticFunctions[$func_name]}();
                        }catch (Exception $e){
                            $errorMsg[] = $e->getMessage();
                        }
                    }
                }

                $data['content'] = str_replace("{{url}}",$this->getStoreUrl(),self::DIAGNOSTICS_REPORT_EMAIL_CONTENT);
                $data['subject'] = self::DIAGNOSTICS_REPORT_EMAIL_SUBJECT . ' : ' . $this->getStoreUrl();


                $data['attachment'] = [
                    'content' => $this->serializer->serialize($diag_data),
                    'filename' => self::DIAGNOSTICS_REPORT_EMAIL_ATTACHMENT_FILENAME,
                    'filetype' => self::DIAGNOSTICS_REPORT_EMAIL_ATTACHMENT_FILETYPE
                ];

                try {
                    $this->sendEmailInterface->send($email, $data);
                    $result = true;
                } catch (Exception $exception) {
                    $result = false;
                }

            }
        } catch (Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $result = false;
        }

        return $this->jsonFactory->create()->setData(['result' => $result]);
    }

    public function getGeneralInfo()
    {

        return $this->diagnosticsReportHelper->getGeneralInfo($this->nitro, $this->configSetting);
    }

    public function getActivePlugins()
    {
        return $this->diagnosticsReportHelper->getActivePluginsStatus();
    }

    public function getUserConfig()
    {
        return (array)$this->diagnosticsReportHelper->getUserConfig($this->nitro, $this->configSetting);


    }

    public function getDirInfo()
    {
        return $this->diagnosticsReportHelper->getDirInfo($this->nitro, $this->configSetting);
    }

    public function getConflictingPlugins()
    {
        return "None detected";
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

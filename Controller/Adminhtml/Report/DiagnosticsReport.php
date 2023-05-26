<?php

namespace NitroPack\NitroPack\Controller\Adminhtml\Report;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Helper\DiagnosticsReport as DiagnosticsReportHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Filesystem\Io\File;


class DiagnosticsReport extends StoreAwareAction
{
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

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var File
     */
    protected $file;


    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param ScopeConfigInterface $_scopeConfig
     * @param ObjectManagerInterface $objectManager
     * @param NitroServiceInterface $nitro
     * @param DiagnosticsReportHelper $diagnosticsReportHelper
     * @param FileFactory $fileFactory
     * @param RawFactory $resultRawFactory
     * @param Filesystem $filesystem
     * @param SerializerInterface $serializer
     * @param File $file
     **/
    public function __construct(
        Context                 $context,
        ScopeConfigInterface    $_scopeConfig,
        ObjectManagerInterface  $objectManager,
        NitroServiceInterface   $nitro,
        DiagnosticsReportHelper $diagnosticsReportHelper,
        FileFactory $fileFactory,
        RawFactory $resultRawFactory,
        Filesystem $filesystem,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        SerializerInterface $serializer,
        File $file

    ) {


        parent::__construct($context, $nitro);
        $this->file = $file;
        $this->fileFactory = $fileFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->filesystem = $filesystem;
        $this->serializer = $serializer;
        $this->diagnosticsReportHelper = $diagnosticsReportHelper;
        $this->objectManager = $objectManager;
        $this->_scopeConfig = $_scopeConfig;

    }

    public function nitroExecute()
    {
        if (!is_null($this->nitro)) {
            $this->configSetting = (array) $this->nitro->getSettings();
        }
        $nitroDiagnosticFunctions = array(
            'general-info-status' => 'getGeneralInfo',
            'active-plugins-status' => 'getActivePlugins',
            'conflicting-plugins-status' => 'getConflictingPlugins',
            'user-config-status' => 'getUserConfig',
            'dir-info-status' => 'getDirInfo',
        );

        //date("Y-m-d H:i:s")
        $diag_data=[];
        try {
            $ar = !empty($this->_request->getParam("toggled")) ? $this->_request->getParam("toggled") : NULL;
            $errorMsg = [];
            if ($ar !== NULL) {

                foreach ($ar as $func_name => $func_allowed) {
                    if ((boolean)$func_allowed) {
                        try{
                        $diag_data[$func_name] =$this->{$nitroDiagnosticFunctions[$func_name]}();
                        }catch (\Exception $e){
                            $errorMsg[] = $e->getMessage();
                        }
                      }
                }

                $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
                $filepath = $directory->getAbsolutePath('nitropack_diag_file.txt');
                if($this->file->fileExists($filepath)){
                    $this->file->rm($filepath);
                }

                $content = $this->serializer->serialize($diag_data);
                $directory->writeFile($filepath, $content);
                $resultRaw = $this->resultRawFactory->create();
                $resultRaw->setHttpResponseCode(200);
                $resultRaw->setHeader('Content-Type', 'application/json', true);
                $resultRaw->setHeader('Content-Disposition', 'attachment; filename=' . 'nitropack_diag_file.txt');
                $resultRaw->setContents($content);
                return $resultRaw;

            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            return false;
        }
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
}

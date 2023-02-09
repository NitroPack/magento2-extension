<?php

namespace NitroPack\NitroPack\Block;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\RequestInterface;
use NitroPack\NitroPack\Model\Telemetry\Reason;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Telemetry extends Template
{

    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $response;

    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var \NitroPack\NitroPack\Model\Telemetry\Reason
     * */
    protected $telemetryReason;

    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;
    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     * */
    protected $directoryList;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     * */
    protected $driverFile;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param Context $context
     * @param \Magento\Framework\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $driverFile
     * @param \NitroPack\NitroPack\Model\Telemetry\Reason $reason
     * @param \Magento\Framework\App\ResponseInterface $header
     * @param NitroServiceInterface $nitro
     * @param RequestInterface $request
     * @param SerializerInterface $serializer
     * @param array $data
     * */
    public function __construct(
        Context $context,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $driverFile,
        \NitroPack\NitroPack\Model\Telemetry\Reason $reason,
        \Magento\Framework\App\ResponseInterface $header,
        NitroServiceInterface $nitro,
        RequestInterface $request,
        SerializerInterface $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->telemetryReason = $reason;
        $this->nitro = $nitro;
        $this->response = $header;
        $this->request = $request;
        $this->directoryList = $directoryList;
        $this->driverFile = $driverFile;
        $this->serializer = $serializer;
    }


    public function checkNitroHeaderCacheIsMiss()
    {
        if ($this->response->getHttpResponseCode() == 200) {
            foreach (headers_list() as $headers) {
                $values = explode(":", $headers);


                if (trim(strtolower($values[0])) == 'x-nitro-cache' && trim($values[1]) == 'MISS') {
                    return true;
                }
            }
        }
        return false;
    }

    public function getPageHeaderMissReason()
    {
        $this->telemetryReason->possibleReason();
        return $this->telemetryReason->getReason();
    }

    public function getPageType()
    {
        return $this->request->getFullActionName();
    }

    public function fetchTelemetryJs()
    {
        $settings = $this->nitro->getSettings();

        if ($settings->enabled && $settings->siteId) {
            $filePath = $this->directoryList->getPath(
                    'var'
                ) . '/nitro_cache/' . $settings->siteId . '/data/' . $settings->siteId . '-config.json';
            if ($this->driverFile->isExists($filePath)) {
                $data = $this->driverFile->fileGetContents($filePath);
                $additionalData = $this->serializer->unserialize($data);
                if ($additionalData['Telemetry']) {
                    return $additionalData['Telemetry'];
                }
            }
        }
        return false;
    }


}

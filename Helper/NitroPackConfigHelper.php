<?php

namespace NitroPack\NitroPack\Helper;



use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\Context as CustomerContextConstants;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\ObjectManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\SDK\HealthStatus;



class NitroPackConfigHelper extends AbstractHelper
{



    protected $errors = [];
    /**
     * @var StateInterface
     * */
    protected $_cacheState;

    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;
    protected static $cachesToEnable = 'full_page';

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     * */
    protected $fileDriver;
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     * */
    protected $serializer;

    /**
     * @var DirectoryList
     * */
    protected $directoryList;
    /**
     * @var  \Magento\Framework\App\State
     * */
    protected $state;
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;
    /**
     * @var RequestInterface
     * */
    protected $request;
    /**
     * @var ObjectManagerInterface
     * */
    private $objectManager;
    /**
     * @var Curl
     * */
    protected $curlClient;

    protected $siteId = null;
    protected $siteSecret = null;
    /**
     * @var ApiHelper
     * */
    protected $apiHelper;
    /**
     * @param Context $context
     * @param \Magento\Framework\App\State $state
     * @param NitroServiceInterface $nitro
     * @param StateInterface $_cacheState
     * @param DirectoryList $directoryList
     * @param RequestInterface $request
     * @param ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param ScopeConfigInterface $_scopeConfig
     * @param Curl $curlClient
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param ApiHelper $apiHelper
     * */
    public function __construct(
        Context                                          $context,
        \Magento\Framework\App\State                     $state,
        NitroServiceInterface                            $nitro,
        StateInterface                                   $_cacheState,
        DirectoryList                                    $directoryList,
        RequestInterface                                 $request,
        ObjectManagerInterface                           $objectManager,
        \Magento\Framework\Filesystem\Driver\File        $fileDriver,
        ScopeConfigInterface                             $_scopeConfig,
        Curl                                             $curlClient,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        ApiHelper                                        $apiHelper

    )
    {
        parent::__construct($context);
        $this->nitro = $nitro;
        $this->apiHelper = $apiHelper;
        $this->curlClient = $curlClient;
        $this->_cacheState = $_cacheState;
        $this->directoryList = $directoryList;
        $this->serializer = $serializer;
        $this->state = $state;
        $this->request = $request;
        $this->objectManager = $objectManager;
        $this->_scopeConfig = $_scopeConfig;
        $this->fileDriver = $fileDriver;
    }


    public function getDisabledCaches()
    {
        $caches = array();
        if (!$this->_cacheState->isEnabled(static::$cachesToEnable)) {
            $caches[] = static::$cachesToEnable;
        }
        return $caches;
    }


    public function getFullPageCacheValue()
    {
        return !is_null(
            $this->_scopeConfig->getValue(\NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK)
        ) && $this->_scopeConfig->getValue(
            \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK
        ) == \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE ? true : false;
    }

    public function setBoolean($option, $value)
    {
        $value = (intval($value) != 0);
        if (strpos($option, '-') === false) {
            $this->nitro->getSettings()->{$option} = $value;
            return;
        }

        $ref = $this->nitro->getSettings();
        $split = explode('-', $option);
        $last = count($split) - 1;

        foreach ($split as $i => $sub) {
            if ($i != $last) {
                $ref = $ref->{$sub};
            } else {
                $ref->{$sub} = $value;
            }
        }
    }

    public function validateSiteCredentials($nitroSiteId, $nitroSiteSecret)
    {
        $siteId = $nitroSiteId;//trim($this->request->getPostValue('nitro_site_id', ""));
        $siteSecret = $nitroSiteSecret;//trim($this->request->getPostValue('nitro_site_secret', ""));

        if (!$siteId) {
            $this->errors['nitro_site_id'] = 'Site ID cannot be blank';
        }

        if (!$siteSecret) {
            $this->errors['nitro_site_secret'] = 'Site secret cannot be blank';
        }

        if (!preg_match("/^([a-zA-Z]{32})$/", $siteId)) {
            $this->errors['nitro_site_id'] = 'Not a valid Site ID';
        }

        if (!preg_match("/^([a-zA-Z0-9]{64})$/", trim($siteSecret))) {
            $this->errors['nitro_site_secret'] = 'Not a valid Site secret';
        }

        $result = empty($this->errors);
        if ($result) {
            $this->siteId = $siteId;
            $this->siteSecret = $siteSecret;
        }

        return $result;
    }

    public function saveSettings($siteId, $siteSecret, $storeCode)
    {
        $this->nitro->setSiteId($siteId);
        $this->nitro->setSiteSecret($siteSecret);
        $this->nitro->persistSettings($storeCode);
    }

    public function persistSettings($storeGroupCode = 'default')
    {
        $this->nitro->persistSettings($storeGroupCode);
    }




    public function xMagentoVaryAdd($storeGroup)
    {
        $storeGroupCode = $storeGroup->getCode();

        try {
            $this->nitro->reload($storeGroupCode);
            if ($this->nitro->isConnected() && $this->nitro->getSdk()->getHealthStatus() == HealthStatus::HEALTHY) {

                list($allCustomerXMagentoVary, $allXMagentoVary) = $this->apiHelper->getAllCustomerGroupDataForPossibleXMagentoVary($storeGroup);
                $x_magento_vary = $allCustomerXMagentoVary;
                $this->nitro->getSdk()->getApi()->setVariationCookie('X-Magento-Vary', $allXMagentoVary, 1);
                $this->nitro->setXMagentoVary($x_magento_vary);
                $this->nitro->persistSettings($storeGroupCode);

            }
        } catch (\Exception $e) {

        }
        return true;
    }




    public function xMagentoVaryDelete($storeGroupCode)
    {
        $settingsFilename = $this->apiHelper->getSettingsFilename($storeGroupCode);
        $haveData = json_decode($this->apiHelper->readFile($settingsFilename), true);

        $groupId = $this->request->getParam('id');

        if (isset($haveData['x_magento_vary'])) {

            foreach ($haveData['x_magento_vary'] as $key => $value) {

                if (isset($value['customer_group']) && $value['customer_group'] == $groupId) {
                    unset($haveData['x_magento_vary'][$key]);
                }
            }
            try {

                $this->nitro->reload($storeGroupCode);
                if ($this->nitro->isConnected() && $this->nitro->getSdk()->getHealthStatus() == HealthStatus::HEALTHY) {

                    $this->nitro->getSdk()->getApi()->setVariationCookie('X-Magento-Vary', array_keys($haveData['x_magento_vary']), 1);
                    $this->nitro->setXMagentoVary($haveData['x_magento_vary']);
                    $this->nitro->persistSettings($storeGroupCode);
                }
            } catch (\Exception $e) {

            }
        }

        return true;

    }

    public function isVarnishConfigured($url)
    {
        $this->curlClient->get($url);
        $responseHeaders = $this->curlClient->getHeaders();
        return isset($responseHeaders['X-Magento-Cache-Debug']) && ($responseHeaders['X-Magento-Cache-Debug'] === 'HIT' || $responseHeaders['X-Magento-Cache-Debug'] === 'MISS');
    }
}

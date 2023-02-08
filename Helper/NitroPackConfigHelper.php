<?php

namespace NitroPack\NitroPack\Helper;


use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filesystem\DirectoryList;
use NitroPack\NitroPack\Api\NitroServiceInterface;


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
     * @param Context $context
     * @param \Magento\Framework\App\State $state
     * @param NitroServiceInterface $nitro
     * @param StateInterface $_cacheState
     * @param DirectoryList $directoryList
     * @param RequestInterface $request
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param ScopeConfigInterface $_scopeConfig
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * */
    public function __construct(
        Context $context,
        \Magento\Framework\App\State $state,
        NitroServiceInterface $nitro,
        StateInterface $_cacheState,
        DirectoryList $directoryList,
        RequestInterface $request,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        ScopeConfigInterface $_scopeConfig,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        parent::__construct($context);
        $this->nitro = $nitro;
        $this->_cacheState = $_cacheState;
        $this->directoryList = $directoryList;
        $this->serializer = $serializer;
        $this->state = $state;
        $this->request = $request;
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

    public function persistSettings()
    {
        $this->nitro->persistSettings('default');
    }


}

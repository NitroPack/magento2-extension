<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace NitroPack\NitroPack\Model\System\Backend;

use Magento\Framework\App\Config\ScopeConfigInterface;
use NitroPack\HttpClient\Exceptions\SocketReadTimedOutException;
use NitroPack\HttpClient\HttpClient;
use NitroPack\HttpClient\HttpClientMulti;

/**
 * Backend model for processing Varnish settings
 *
 * Class Varnish
 */
class VarnishNitroPackConfig extends \Magento\Framework\App\Config\Value
{
    /**
     * @var array
     */
    protected $defaultValues;
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $_curl;

    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        ScopeConfigInterface                                    $config,
        \Magento\Framework\App\Cache\TypeListInterface          $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        \Magento\Framework\HTTP\Client\Curl                     $curl,

        array                                                   $data = []
    )
    {
        $this->_curl = $curl;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Set default data if empty fields have been left
     *
     * @return $this|\Magento\Framework\Model\AbstractModel
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        $data = $this->_getDefaultValues();
        $currentValue = $this->getValue();

        if (!$currentValue) {
            $replaceValue = isset($data[$this->getField()]) ? $data[$this->getField()] : false;
            $this->setValue($replaceValue);
        }
        $host = $this->_config->getValue('system/full_page_cache/default/backend_host', $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null);

        $url = 'http://' . $host . ':' . $currentValue;

        $httpMulti = new HttpClientMulti();
        $client = new HttpClient($url);
        $client->hostOverride($client->host, $host);
        $client->doNotDownload = true;
        $client->setHeader('X-Magento-Tags-Pattern:', '.*');
        $httpMulti->push($client);
        $res = $httpMulti->fetchAll(true, 'purge');
        if(isset($res[1])){
        foreach ($res[1] as $failedRequest) {
            $exception = $failedRequest[1];
            if ($exception instanceof SocketReadTimedOutException) {
                continue; // Ignore read timeouts
            } else {
                throw $exception;
            }
        }
        }
        $this->setValue($currentValue);
        return $this;
    }

    /**
     * Get Default Config Values
     *
     * @return array
     */
    protected function _getDefaultValues()
    {
        if (!$this->defaultValues) {
            $this->defaultValues = ['varnish_port' => 80];
        }

        return $this->defaultValues;
    }

    /**
     * If fields are empty fill them with default data
     *
     * @return $this|\Magento\Framework\Model\AbstractModel
     */
    protected function _afterLoad()
    {
        $data = $this->_getDefaultValues();
        $currentValue = $this->getValue();
        if (!$currentValue) {
            foreach ($data as $field => $value) {
                if (is_string($this->getPath()) && strstr($this->getPath(), (string)$field)) {
                    $this->setValue($value);
                    $this->save();
                    break;
                }
            }
        }
        return $this;
    }
}

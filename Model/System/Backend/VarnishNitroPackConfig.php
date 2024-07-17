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

namespace NitroPack\NitroPack\Model\System\Backend;

use GraphQL\Utils\Value;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\FlagManager;
use NitroPack\HttpClient\Exceptions\SocketReadTimedOutException;
use NitroPack\HttpClient\HttpClient;
use NitroPack\HttpClient\HttpClientMulti;

/**
 * Class VarnishNitroPackConfig - Backend model for processing Varnish settings configuration
 * @package NitroPack\NitroPack\Model\ResourceModel
 * @since 3.2.1
 * @extends \Magento\Framework\App\Config\Value
 **/
class VarnishNitroPackConfig extends \Magento\Framework\App\Config\Value
{
    /**
     * @var array
     */
    protected $defaultValues;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var FlagManager
     */
    private $flagManager;

    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        ScopeConfigInterface                                    $config,
        \Magento\Framework\App\Cache\TypeListInterface          $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        \Magento\Framework\Message\ManagerInterface             $messageManager,
        FlagManager                                             $flagManager,
        array                                                   $data = []
    )
    {
        $this->flagManager = $flagManager;
        $this->messageManager = $messageManager;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return $this|VarnishNitroPackConfig
     */
    public function beforeSave()
    {
        $currentValues = explode(',', $this->getValue());
        $successfulValues = [];

        foreach ($currentValues as $currentValue) {
            $currentValue = trim($currentValue);
            if($currentValue == '')
                continue;

            if(strpos($currentValue,'localhost') !== false )
                $currentValue = str_replace('localhost','127.0.0.1',$currentValue) ;

            $data = explode(':', $currentValue);
            if (isset($data[0])) {
                $url = $this->constructUrl($data);
                $client = $this->createHttpClient($url, $data[0]);

                $httpMulti = new HttpClientMulti();
                $httpMulti->push($client);
                $res = $httpMulti->fetchAll(true, 'purge');

                if (isset($res[1])) {
                    if ($this->handleExceptions($res)) {
                        continue;
                    }
                }
                $successfulValues[] = $currentValue;
            }
        }
      if(count($successfulValues)>0)
        $this->setValue($successfulValues);

        return $this;
    }

    /**
     * @param $data
     * @return string
     */
    private function constructUrl($data)
    {
        $scheme = 'http://';
        if (isset($data[1]))
            return $data[0] . ':' . $data[1];
        else
            return $data[0];
    }

    /**
     * @param $url
     * @param $host
     * @return HttpClient
     */
    private function createHttpClient($url, $host)
    {
        $client = new HttpClient($url);
        $client->hostOverride($client->host, $host);
        $client->doNotDownload = true;
        $client->setHeader('X-Magento-Tags-Pattern:', '.*');

        return $client;
    }

    /**
     * @param $res
     * @return bool
     */
    private function handleExceptions($res)
    {
        foreach ($res[1] as $failedRequest) {
            $exception = $failedRequest[1];
            if ($exception instanceof SocketReadTimedOutException) {
                continue; // Ignore read timeouts
            } else {
                $this->messageManager->addError(__("The provided Reverse Proxy settings are not correct. They are saved but NitroPack may not work correctly. Please check them again."));
                $this->flagManager->saveFlag('nitro_varnish_mismatch_message', []);
                return true;
            }
        }
        $this->flagManager->deleteFlag('nitro_varnish_mismatch_message');
        return false;
    }

}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace NitroPack\NitroPack\Model\System\Backend;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\FlagManager;
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
        FlagManager             $flagManager,
        array                                                   $data = []
    )
    {
        $this->flagManager = $flagManager;
        $this->messageManager = $messageManager;

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
        $currentValue = $this->getValue();

        if (!$currentValue) {
            $replaceValue = isset($data[$this->getField()]) ? $data[$this->getField()] : false;
            $this->setValue($replaceValue);
        }

        $data = explode(':', $currentValue);
        if (isset($data[0])) {

            $scheme = 'http://';
            if (isset($data[1]))
                $url =  $data[0] . ':' . $data[1];
            else
                $url = $data[0];

            if (isset($data[1]) && $data[1] == 80) {
                $currentValue = $data[0];
            }

            $httpMulti = new HttpClientMulti();
            $client = new HttpClient($url);

            $client->hostOverride($client->host, $data[0]);
            $client->doNotDownload = true;
            $client->setHeader('X-Magento-Tags-Pattern:', '.*');
            $httpMulti->push($client);
            $res = $httpMulti->fetchAll(true, 'purge');
            if (isset($res[1])) {
                foreach ($res[1] as $failedRequest) {
                    $exception = $failedRequest[1];
                    if ($exception instanceof SocketReadTimedOutException) {
                        continue; // Ignore read timeouts
                    } else {
                        if ($exception instanceof \NitroPack\HttpClient\Exceptions\SocketWriteException) {
                            $this->messageManager->addError(__("The provided Reverse Proxy settings are not correct. They are saved but NitroPack may not work correctly. Please check them again."));
                            $this->setValue($currentValue);
                            $this->flagManager->saveFlag('nitro_varnish_mismatch_message',[]);
                            return false;
                        } else {
                            $this->messageManager->addError(__("The provided Reverse Proxy settings are not correct. They are saved but NitroPack may not work correctly. Please check them again."));
                            $this->setValue($currentValue);
                            $this->flagManager->saveFlag('nitro_varnish_mismatch_message',[]);
                            return false;
                        }
                    }
                }
            }
            $this->flagManager->deleteFlag('nitro_varnish_mismatch_message');
            $this->setValue($currentValue);
        }
        return $this;
    }

}

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
namespace NitroPack\NitroPack\Observer\Customer;

use Magento\Framework\App\Http\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;

use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\ApiHelper;
use NitroPack\SDK\HealthStatus;
use NitroPack\NitroPack\Logger\Logger;
/**
 * Class LoginObserver - When a customer logs in, add the store group to the vary header
 * @implements ObserverInterface
 * @package NitroPack\NitroPack\Observer\Customer
 * @since 2.0.0
 * */
class LoginObserver implements ObserverInterface
{

    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var StoreManagerInterface
     * */
    protected $storeManager;
    /**
     * @var Context
     * */
    protected $context;
    /**
     * @var NitroServiceInterface
     * */
    public $nitro;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     * */
    protected $fileDriver;

    /**
     * @var ApiHelper
     * */
    private $apiHelper;
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     * */
    protected $serializer;

    public function __construct(
        Logger                                           $logger,
        Context                                          $context,
        NitroServiceInterface                            $nitroService,
        ApiHelper                                        $apiHelper,
        StoreManagerInterface                            $storeManager,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Framework\Filesystem\Driver\File        $fileDriver
    )
    {
        $this->apiHelper = $apiHelper;
        $this->logger = $logger;
        $this->context = $context;
        $this->nitro = $nitroService;
        $this->fileDriver = $fileDriver;
        $this->serializer = $serializer;
        $this->storeManager = $storeManager;
    }

    public function execute(Observer $observer)
    {
        $this->nitro->reload($this->storeManager->getGroup()->getCode());
        if (!is_null($this->nitro->getSdk()) && $this->nitro->getSdk()->getHealthStatus() == HealthStatus::HEALTHY) {
            $settingsFilename = $this->apiHelper->getSettingsFilename($this->storeManager->getGroup()->getCode());
            $haveData = $this->apiHelper->readFile($settingsFilename);
            $settings = json_decode($haveData);
            if(isset($settings->cache_to_login_customer) && $settings->cache_to_login_customer){
            $cookieVariation = $this->nitro->getSdk()->getApi()->getVariationCookies();
            $filteredCookieVariation = array_filter($cookieVariation, function ($cookieVariationHave) {
                return $cookieVariationHave['name'] == 'X-Magento-Vary';
            });
            if (count($filteredCookieVariation) > 0) {
                $filteredCookieVariationValues = array_column($filteredCookieVariation, 'values');
                if (!is_null($this->context->getVaryString()) && isset($filteredCookieVariationValues[0]) && is_array($filteredCookieVariationValues[0]) && !in_array($this->context->getVaryString(), $filteredCookieVariationValues[0])) {
                    array_push($filteredCookieVariationValues[0], $this->context->getVaryString());
                    $xMagentoVary = (array)$settings->x_magento_vary;
                    if (!in_array($this->context->getVaryString(), $xMagentoVary)) {
                        $xMagentoVary = array_merge($xMagentoVary, [$this->context->getVaryString() => $this->context->getData()]);
                        $settings->x_magento_vary = $xMagentoVary;
                    }

                    $this->nitro->getSdk()->getApi()->setVariationCookie('X-Magento-Vary', $filteredCookieVariationValues[0], 1);
                }
            } else {
                $settings->x_magento_vary = [$this->context->getVaryString() => $this->context->getData()];
                $this->nitro->getSdk()->getApi()->setVariationCookie('X-Magento-Vary', $this->context->getVaryString(), 1);
            }

            $this->fileDriver->filePutContents($settingsFilename, $this->serializer->serialize($settings));
        }
        }
    }
}

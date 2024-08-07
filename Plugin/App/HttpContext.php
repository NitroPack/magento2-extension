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

namespace NitroPack\NitroPack\Plugin\App;

use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\ApiHelper;
use NitroPack\SDK\HealthStatus;

/**
 * Class HttpContext - Http Context Plugin Set Value For Session Variable for Cookie Variation
 * @package NitroPack\NitroPack\Plugin\App
 * @since 2.0.0
 */
class HttpContext
{
    /**
     * @var StoreManagerInterface
     * */
    protected $storeManager;
    /**
     * @var NitroService
     * */
    protected $nitro;
    /**
     * @var ApiHelper
     * */
    protected $apiHelper;
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     * */
    protected $serializer;
    /**
     * @var \Magento\Framework\App\RequestInterface
     * */
    protected $request;
    /**
     * @param StoreManagerInterface $storeManager
     * @param NitroService $nitroService
     * @param ApiHelper $apiHelper
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        NitroService $nitroService,
        ApiHelper $apiHelper,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Framework\App\RequestInterface $request
    )
    {
        $this->nitro = $nitroService;
        $this->storeManager = $storeManager;
        $this->apiHelper = $apiHelper;
        $this->serializer = $serializer;
        $this->request = $request;
    }

    /**
     * Data setter
     * @param string $name
     * @param mixed $value
     * @param mixed $default
     * @return \Magento\Framework\App\Http\Context
     */
    public function beforeSetValue(\Magento\Framework\App\Http\Context $subject, $name, $value, $default)
    {
        $request = $this->request;
        // Get the User-Agent from the request headers
        $userAgent = $this->request->getServer('HTTP_USER_AGENT');
        // ||  !is_null($request->get('PHPSESSID'))
        $setValueFor = ['customer_logged_in','customer_group','store'];
         if (!is_null($userAgent) &&  in_array($name,$setValueFor) && (strpos($userAgent, 'Nitro-Optimizer-Agent') !== false || strpos($userAgent, 'Nitro-Webhook-Agent') !== false) && $request->get('X-Magento-Vary')) {

            $this->storeManager->getGroup()->getCode();
            $this->nitro->reload($this->storeManager->getGroup()->getCode());
            if (!is_null($this->nitro->getSdk()) && $this->nitro->getSdk()->getHealthStatus() == HealthStatus::HEALTHY) {
                $settingsFilename = $this->apiHelper->getSettingsFilename($this->storeManager->getGroup()->getCode());
                $haveData = $this->apiHelper->readFile($settingsFilename);

                    $serialData = $this->serializer->unserialize($haveData);
                if (isset($serialData['cache_to_login_customer']) && $serialData['cache_to_login_customer']) {
                    if (isset($serialData['x_magento_vary'][$request->get('X-Magento-Vary')])) {
                        if (in_array($name, $setValueFor))
                            $value = isset($serialData['x_magento_vary'][$request->get('X-Magento-Vary')][$name]) ? $serialData['x_magento_vary'][$request->get('X-Magento-Vary')][$name] : $value;

                    }

                }
            }
        }
        return [$name, $value, $default];
    }

}

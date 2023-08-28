<?php

namespace NitroPack\NitroPack\Model\App;

use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\ApiHelper;
use NitroPack\SDK\HealthStatus;
use \Magento\Framework\Serialize\SerializerInterface;

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
     * @param StoreManagerInterface $storeManager
     * @param NitroService $nitroService
     * @param ApiHelper $apiHelper
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     */
    public function __construct(StoreManagerInterface $storeManager, NitroService $nitroService, ApiHelper $apiHelper, \Magento\Framework\Serialize\SerializerInterface $serializer)
    {
        $this->nitro = $nitroService;
        $this->storeManager = $storeManager;
        $this->apiHelper = $apiHelper;
        $this->serializer = $serializer;
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
        $request = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Framework\App\RequestInterface::class);
        // Get the User-Agent from the request headers
        $userAgent = $request->getServer('HTTP_USER_AGENT');
        // ||  !is_null($request->get('PHPSESSID'))
        $setValueFor = ['customer_logged_in','customer_group','store'];
         if (in_array($name,$setValueFor) && (strpos($userAgent, 'Nitro-Optimizer-Agent') !== false || strpos($userAgent, 'Nitro-Webhook-Agent') !== false) && $request->get('X-Magento-Vary')) {
            $this->storeManager->getGroup()->getCode();
            $this->nitro->reload($this->storeManager->getGroup()->getCode());
            if (!is_null($this->nitro->getSdk()) && $this->nitro->getSdk()->getHealthStatus() == HealthStatus::HEALTHY) {
                $settingsFilename = $this->apiHelper->getSettingsFilename($this->storeManager->getGroup()->getCode());
                $haveData = $this->apiHelper->readFile($settingsFilename);
                $serialData = $this->serializer->unserialize($haveData);
                if (isset($serialData['x_magento_vary'][$request->get('X-Magento-Vary')])) {
                    if (in_array($name,$setValueFor))
                        $value = isset($serialData['x_magento_vary'][$request->get('X-Magento-Vary')][$name])? $serialData['x_magento_vary'][$request->get('X-Magento-Vary')][$name] : $value;

                }

            }
        }
        return [$name, $value, $default];
    }

}

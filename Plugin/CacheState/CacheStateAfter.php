<?php

namespace NitroPack\NitroPack\Plugin\CacheState;

use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use NitroPack\NitroPack\Api\NitroService;

class CacheStateAfter
{
    /**
     * Cache state service
     *
     * @var StateInterface
     */
    private $cacheState;

    /**
     * @var EventManager
     */
    private $eventManager;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     * */
    protected $scopeConfig;

    public function __construct(
        StateInterface $cacheState,
        EventManager $eventManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->cacheState = $cacheState;
        $this->eventManager = $eventManager;
        $this->scopeConfig = $scopeConfig;
    }

    private $checkCaches = ['full_page'];

    public function afterSetEnabled(\Magento\Framework\App\Cache\Manager $subject, $result)
    {
        $status = false;
        $cacheIsTrigger = false;
        foreach ($result as $resultValue) {
            if (in_array($resultValue, $this->checkCaches)) {
                if (!$this->cacheState->isEnabled($resultValue)) {
                    $status = true;
                }
                $cacheIsTrigger = true;
            }
        }

        //Enabled the Extension
        if ($status && $cacheIsTrigger) {
            $this->eventManager->dispatch('nitropack_cache_event_after', ['cache_enabled' => true, 'extension' => false]
            );
        }
        //disabled the Extension
        if (!$status && $cacheIsTrigger) {
            $this->eventManager->dispatch('nitropack_cache_event_after', ['cache_enabled' => false, 'extension' => true]
            );
        }
        return $result;
    }


    public function afterFlush(\Magento\Framework\App\Cache\Manager $subject, $result, $types)
    {
        if (!is_null(
                $this->scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK)
            ) && in_array($this->scopeConfig->getValue(
                NitroService::FULL_PAGE_CACHE_NITROPACK
            ),[ NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE ,NitroService::FASTLY_CACHING_APPLICATION_VALUE]) && $this->cacheState->isEnabled(
                'full_page'
            ) && in_array('full_page', $types)) {

            if($this->scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK) == NitroService::FASTLY_CACHING_APPLICATION_VALUE && $this->scopeConfig->getValue(NitroService::XML_FASTLY_PAGECACHE_ENABLE_NITRO)!=1){
                return $result;
            }

            $this->eventManager->dispatch('nitropack_cache_flush_after', []);
        }
        return $result;
    }
}

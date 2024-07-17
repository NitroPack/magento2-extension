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
namespace NitroPack\NitroPack\Plugin\CacheState;

use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use NitroPack\NitroPack\Api\NitroService;
use Magento\Framework\Registry;

/**
 * Class CacheStateAfter - Cache State After Plugin
 * @package NitroPack\NitroPack\Plugin\CacheState
 * @since 2.0.0
 * */
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

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param StateInterface $cacheState
     * @param EventManager $eventManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Registry $registry
     */
    public function __construct(
        StateInterface $cacheState,
        EventManager $eventManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Registry $registry
    ) {
        $this->cacheState = $cacheState;
        $this->eventManager = $eventManager;
        $this->scopeConfig = $scopeConfig;
        $this->registry = $registry;
    }

    private $checkCaches = ['full_page'];

    public function afterSetEnabled(\Magento\Framework\App\Cache\Manager $subject, $result)
    {
        $setupMode = $this->registry->registry('setup-mode-enabled');

        if ($setupMode) {
            return $result;
        }

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

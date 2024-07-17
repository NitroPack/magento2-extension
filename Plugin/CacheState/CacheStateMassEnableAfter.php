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
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
/**
 * Class CacheStateMassEnableAfter - Cache State Mass Enable After
 * @package NitroPack\NitroPack\Plugin\CacheState
 * @since 2.0.0
 * */
class CacheStateMassEnableAfter
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
     * @var \Magento\Framework\Controller\ResultFactory
     * */
    private $resultFactory;

    public function __construct(
        StateInterface $cacheState,
        EventManager $eventManager,
        \Magento\Framework\Controller\ResultFactory $resultFactory
    ) {
        $this->cacheState = $cacheState;
        $this->eventManager = $eventManager;
        $this->resultFactory = $resultFactory;
    }

    private $checkCaches = 'full_page';

    public function afterExecute()
    {
        $status = false;
        $cacheIsTrigger = false;
        $data = 'full_page';
        if (!$this->cacheState->isEnabled($data)) {
            $status = true;
        }
        $cacheIsTrigger = true;


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

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('adminhtml/*');
    }
}

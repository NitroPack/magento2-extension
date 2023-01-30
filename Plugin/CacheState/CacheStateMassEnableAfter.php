<?php

namespace NitroPack\NitroPack\Plugin\CacheState;

use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;

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

<?php

namespace NitroPack\NitroPack\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use NitroPack\NitroPack\Helper\InvalidationHelper;

class ProcessCronQueueObserver implements ObserverInterface
{
    /**
     * @var InvalidationHelper
     * */
    protected $invalidationHelper;

    public function __construct(InvalidationHelper $invalidationHelper)
    {
        $this->invalidationHelper = $invalidationHelper;
    }

    function execute(Observer $observer)
    {
        $this->invalidationHelper->makeConnectionsDisableAndEnable(true);
    }
}

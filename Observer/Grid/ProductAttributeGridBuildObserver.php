<?php

namespace NitroPack\NitroPack\Observer\Grid;
use Magento\Framework\Module\Manager;
use Magento\Framework\Event\ObserverInterface;

/**
 * Product attribute grid build observer
 */
class ProductAttributeGridBuildObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * Construct.
     *
     * @param Manager $moduleManager
     */
    public function __construct(Manager $moduleManager)
    {
        $this->moduleManager = $moduleManager;
    }

    /**
     * Execute.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->moduleManager->isOutputEnabled('NitroPack_NitroPack')) {
            return;
        }

        /** @var \Magento\Catalog\Block\Adminhtml\Product\Attribute\Grid $grid */
        $grid = $observer->getGrid();
        $grid->addColumnAfter(
            'nitro_purge',
            [
                'header' => __('Purge Nitro Cache'),
                'sortable' => true,
                'index' => 'nitro_purge',
                'type' => 'options',
                'options' => ['1' => __('Yes'), '0' => __('No')],
                'align' => 'center',
            ],
            'is_global'
        );
    }
}

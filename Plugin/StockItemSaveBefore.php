<?php

namespace NitroPack\NitroPack\Plugin;

class StockItemSaveBefore
{
    public function beforeSave(\Magento\CatalogInventory\Model\Stock\StockItemRepository $subject,\Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem)
    {

        // Fetch Previous qty
        if(!is_null($stockItem->getItemId())){
            $data = $subject->get($stockItem->getItemId());
            $stockItem->setOrigData('qty', (int)$data->getQty());

        }
    }
}

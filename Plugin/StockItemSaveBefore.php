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
namespace NitroPack\NitroPack\Plugin;

/**
 * Class StockItemSaveBefore - Stock Item Save Before
 * @package NitroPack\NitroPack\Plugin
 * @since 2.4.0
 * */
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

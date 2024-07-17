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
namespace NitroPack\NitroPack\Model\Dashboard;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;

/**
 * Class ProductAttributeBlock - Product Attribute Block ViewModel
 * @extends \Magento\Framework\View\Element\Block\ArgumentInterface
 * @package NitroPack\NitroPack\Model\Dashboard
 * @since 2.0.0
 * */
class ProductAttributeBlock implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     * */
    protected $attributeCollectionFactory;

    public function __construct(CollectionFactory $attributeCollectionFactory)
    {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    public function getNitroPackAttribute()
    {
        $attributeCollection = $this->attributeCollectionFactory->create();
        $attributeCollection->addFieldToFilter('frontend_label', array('notnull' => true))->setOrder('frontend_label', 'ASC')
            ->setPageSize(10)
            ->setCurPage(1);


        return $attributeCollection->toArray();
    }

}

<?php

namespace NitroPack\NitroPack\Model\Dashboard;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;

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

<?php
namespace NitroPack\NitroPack\Model\ResourceModel\EntityAttributeRelation;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection {

	protected $_idFieldName = 'entity_attribute_id';
	protected $_eventPrefix = 'nitropack_eav_entity_attribute_collection';
	protected $_eventObject = 'nitropack_entity_attribute_relation';

	protected function _construct()	{
		$this->_init(
			\NitroPack\NitroPack\Model\EntityAttributeRelation::class,
			\NitroPack\NitroPack\Model\ResourceModel\EntityAttributeRelation::class
		);
	}

}
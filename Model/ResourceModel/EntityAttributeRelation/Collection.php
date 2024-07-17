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
namespace NitroPack\NitroPack\Model\ResourceModel\EntityAttributeRelation;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection - Entity Attribute Relation Collection
 * @package NitroPack\NitroPack\Model\ResourceModel\EntityAttributeRelation
 * @since 2.6.0
 * @extends AbstractCollection
 * */
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

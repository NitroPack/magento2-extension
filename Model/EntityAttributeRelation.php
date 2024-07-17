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
namespace NitroPack\NitroPack\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Class EntityAttributeRelation - Entity Attribute Relation Model
 * @package NitroPack\NitroPack\Model
 * @since 2.8.0
 * */
class EntityAttributeRelation extends AbstractModel implements IdentityInterface {

	const CACHE_TAG = 'nitropack_eav_entity_attribute';

	protected $_cacheTag = 'nitropack_eav_entity_attribute';

	protected $_eventPrefix = 'nitropack_eav_entity_attribute';

	protected function _construct() {
		$this->_init(\NitroPack\NitroPack\Model\ResourceModel\EntityAttributeRelation::class);
	}

	public function getIdentities() {
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

	public function getDefaultValues() {
		$values = [];
		return $values;
	}
}

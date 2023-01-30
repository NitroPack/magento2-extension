<?php
namespace NitroPack\NitroPack\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;

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
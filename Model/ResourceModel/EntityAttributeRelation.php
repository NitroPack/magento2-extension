<?php
namespace NitroPack\NitroPack\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class EntityAttributeRelation extends AbstractDb {

	public function __construct(Context $context) {
		parent::__construct($context);
	}

	protected function _construct() {
		$this->_init('eav_entity_attribute', 'entity_attribute_id');
	}

}
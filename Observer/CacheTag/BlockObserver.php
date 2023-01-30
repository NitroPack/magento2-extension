<?php
namespace NitroPack\NitroPack\Observer\CacheTag;

use Magento\Framework\Event\Observer;
use Magento\Cms\Model\Block;

use NitroPack\NitroPack\Observer\CacheTagObserver;

class BlockObserver extends CacheTagObserver {

	protected static $eventMap = array(
		'cms_block_load_after' => 'loaded'
	);

	protected $block; // Magento\Cms\Model\Block

	public function prepareData(Observer $observer) {
		$data = $observer->getEvent()->getData();

		if (!isset($data['object']) || !is_a($data['object'], Block::class)) {
			return false;
		}

		$this->block = $data['object'];

		return true;
	}

	public function loaded(Observer $observer) {
		$this->tagger->tagBlock($this->block);
	}
	
}

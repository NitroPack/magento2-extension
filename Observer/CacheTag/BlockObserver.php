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
namespace NitroPack\NitroPack\Observer\CacheTag;

use Magento\Framework\Event\Observer;
use Magento\Cms\Model\Block;

use Magento\Framework\Event\ObserverInterface;
use NitroPack\NitroPack\Observer\CacheTagObserver;

/**
 * Class BlockObserver - Observer for CMS Block cache tagger
 * @implements ObserverInterface
 * @package NitroPack\NitroPack\Observer\CacheTag
 * @since 2.0.0
 */
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

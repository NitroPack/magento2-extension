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
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

use NitroPack\NitroPack\Observer\CacheTagObserver;

/**
 * Class ProductObserver - Observer for Product cache tagger
 * @extends  CacheTagObserver
 * @package NitroPack\NitroPack\Observer\CacheTag
 * @since 2.0.0
 */
class ProductObserver extends CacheTagObserver {

	protected static $eventMap = array(
		'catalog_product_load_after'            => 'productLoaded',
		'catalog_product_collection_load_after' => 'collectionLoaded'
	);

	protected $product; // Magento\Catalog\Model\Product
	protected $collection; // Magento\Catalog\Model\ResourceModel\Product\Collection

	public function prepareData(Observer $observer) {
		$data = $observer->getEvent()->getData();
		$eventName = $observer->getEvent()->getName();

		switch ($eventName) {
			case 'catalog_product_load_after':
				if (!isset($data['product']) || !is_a($data['product'], Product::class)) {
					return false;
				}
				$this->product = $data['product'];
				break;
			case 'catalog_product_collection_load_after':
				if (!isset($data['collection']) || !is_a($data['collection'], Collection::class)) {
					return false;
				}
				$this->collection = $data['collection'];
				break;
			default:
				return false;
		}

		return true;
	}

	public function productLoaded(Observer $observer) {
		$this->tagger->tagProduct($this->product);
	}

	public function collectionLoaded(Observer $observer) {
		foreach ($this->collection as $product) {
			$this->tagger->tagProduct($product);
		}
	}

}

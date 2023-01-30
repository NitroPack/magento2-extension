<?php
namespace NitroPack\NitroPack\Observer\CacheTag;

use Magento\Framework\Event\Observer;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

use NitroPack\NitroPack\Observer\CacheTagObserver;

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

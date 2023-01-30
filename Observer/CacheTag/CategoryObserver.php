<?php
namespace NitroPack\NitroPack\Observer\CacheTag;

use Magento\Framework\Event\Observer;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;

use NitroPack\NitroPack\Observer\CacheTagObserver;

class CategoryObserver extends CacheTagObserver {

	protected static $eventMap = array(
		'catalog_category_load_after'            => 'categoryLoaded',
		'catalog_category_collection_load_after' => 'collectionLoaded'
	);

	protected $category; // Magento\Catalog\Model\Category
	protected $collection; // Magento\Catalog\Model\ResourceModel\Category\Collection

	public function prepareData(Observer $observer) {
		$data = $observer->getEvent()->getData();
		$eventName = $observer->getEvent()->getName();

		switch ($eventName) {
			case 'catalog_category_load_after':
				if (!isset($data['category']) || !is_a($data['category'], Category::class)) {
					return false;
				}
				$this->category = $data['category'];
				break;
			case 'catalog_category_collection_load_after':
				if (!isset($data['category_collection']) || !is_a($data['category_collection'], Collection::class)) {
					return false;
				}
				$this->collection = $data['category_collection'];
				break;
			default:
				return false;
		}

		return true;
	}

	public function categoryLoaded(Observer $observer) {
		$this->tagger->tagCategory($this->category);
	}

	public function collectionLoaded(Observer $observer) {
		foreach ($this->collection as $category) {
			// $this->tagger->tagCategory($category);
		}
	}
	
}

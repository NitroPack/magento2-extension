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
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;

use Magento\Framework\Event\ObserverInterface;
use NitroPack\NitroPack\Observer\CacheTagObserver;

/**
 * Class CategoryObserver - Observer for Category cache tagger
 * @implements ObserverInterface
 * @package NitroPack\NitroPack\Observer\CacheTag
 * @since 2.0.0
 */
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
                if($data['category']->getId()!=$this->getRootCategoryId())
				    $this->category = $data['category'];
                else
                    return  false;
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

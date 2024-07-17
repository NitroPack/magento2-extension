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
use Magento\Cms\Model\Page;

use Magento\Framework\Event\ObserverInterface;
use NitroPack\NitroPack\Observer\CacheTagObserver;

/**
 * Class PageObserver - Observer for Page cache tagger
 * @implements ObserverInterface
 * @package NitroPack\NitroPack\Observer\CacheTag
 * @since 2.0.0
 */
class PageObserver extends CacheTagObserver {

	protected static $eventMap = array(
		'cms_page_load_after' => 'loaded',
		'cms_page_render'     => 'loaded'
	);

	protected $page; // Magento\Cms\Model\Page
	protected $controllerAction;
	protected $request;

	public function prepareData(Observer $observer) {
		$data = $observer->getEvent()->getData();
		$eventName = $observer->getEvent()->getName();

		switch ($eventName) {
			case 'cms_page_load_after':
				if (!isset($data['object']) || !is_a($data['object'], Page::class)) {
					return false;
				}
				$this->page = $data['object'];
				break;
			case 'cms_page_render':
				if (!isset($data['page']) || !is_a($data['page'], Page::class) || !isset($data['controller_action']) || !isset($data['request'])) {
					return false;
				}
				$this->page = $data['page'];
				$this->controllerAction = $data['controller_action'];
				$this->request = $data['request'];
				break;
			default:
				return false;
		}

		return true;
	}

	public function loaded(Observer $observer) {
		$this->tagger->tagPage($this->page);
	}

}

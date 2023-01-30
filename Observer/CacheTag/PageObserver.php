<?php
namespace NitroPack\NitroPack\Observer\CacheTag;

use Magento\Framework\Event\Observer;
use Magento\Cms\Model\Page;

use NitroPack\NitroPack\Observer\CacheTagObserver;

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

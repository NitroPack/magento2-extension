<?php
namespace NitroPack\NitroPack\Api;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Category;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\Block;

class TaggingService implements TaggingServiceInterface {

	protected $initialized = false;
	protected $doNotPush;
	protected $nitro = null;
	protected $enabled = false;

	protected $currentRequestTags = null;

	public function __construct(NitroServiceInterface $nitro, $doNotPush = false) {
		$this->nitro = $nitro;
		$this->doNotPush = $doNotPush;
	}

	protected function initialize() {
		// We separate the initialization and check it before tagging, since calling isCacheable in the constructor will lead to a dependency loop
		$this->enabled = (!$this->doNotPush && $this->nitro->isConnected() && $this->nitro->isEnabled() && $this->nitro->isCacheable());

		$this->initialized = true;

		if (!$this->enabled) {
			return;
		}

		$this->currentRequestTags = array();
		register_shutdown_function(array($this, 'onShutdown'));
	}

	protected function tag($tag) {
		if (!$this->initialized) {
			$this->initialize();
		}
		if (!$this->enabled) {
			return;
		}

		if (!in_array($tag, $this->currentRequestTags)) {
			$this->currentRequestTags[] = $tag;
		}
	}

	public function onShutdown() {
        $this->nitro->tagUrl($this->nitro->getUrl(), $this->currentRequestTags);

    }

	public function getProductTag($product) {
		if (is_int($product)) {
			$productId = $product;
		} else {
			if (!is_a($product, Product::class)) return false;
			$productId = $product->getId();
		}

		return 'cat_p_' . $productId;
	}

	public function getCategoryTag($category) {
		if (is_int($category)) {
			$categoryId = $category;
		} else {
			if (!is_a($category, Category::class)) return false;
			$categoryId = $category->getId();
		}

		return 'cat_c_' . $categoryId;
	}

	public function getPageTag($page) {
		if (is_int($page)) {
			$pageId = $page;
		} else {
			if (!is_a($page, Page::class)) return false;
			$pageId = $page->getId();
		}

		return 'page:' . $pageId;
	}

	public function getBlockTag($block) {
		if (is_int($block)) {
			$blockId = $block;
		} else {
			if (!is_a($block, Block::class)) return false;
			$blockId = $block->getId();
		}

		return 'cms_b_' . $blockId;
	}

	public function tagProduct($product) {
		if ($tag = $this->getProductTag($product)) {
			$this->tag($tag);
		}
	}

	public function tagCategory($category) {
		if ($tag = $this->getCategoryTag($category)) {
			$this->tag($tag);
		}
	}

	public function tagPage($page) {
		if ($tag = $this->getPageTag($page)) {
			$this->tag('page');
			$this->tag($tag);
		}
	}

	public function tagBlock($block) {
		if ($tag = $this->getBlockTag($block)) {
			$this->tag('block');
			$this->tag($tag);
		}
	}

    public function addTag($tag){
        $this->tag($tag);
    }
}

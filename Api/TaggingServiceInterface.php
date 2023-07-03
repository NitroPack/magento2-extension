<?php
namespace NitroPack\NitroPack\Api;

interface TaggingServiceInterface {

	public function tagProduct($product);
	public function tagCategory($category);
	public function tagPage($page);
	public function tagBlock($block);

	public function getProductTag($product);
	public function getCategoryTag($category);
	public function getPageTag($page);
	public function getBlockTag($block);

    public function addTag($tag);
}

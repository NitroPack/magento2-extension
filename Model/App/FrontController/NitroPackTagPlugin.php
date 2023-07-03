<?php

namespace NitroPack\NitroPack\Model\App\FrontController;


use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\PageCache\Model\Cache\Type as CacheType;
use Magento\PageCache\Model\Config;
use NitroPack\NitroPack\Api\NitroService;

class NitroPackTagPlugin
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var \NitroPack\NitroPack\Api\TaggingServiceInterface
     * */
    protected $tagger;
    /**
     * @var \Magento\Cms\Api\BlockRepositoryInterface
     * */
    protected $blockRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     * */
    protected $searchCriteriaBuilder;

    /**
     * @param Config $config
     * @param Registry $registry
     * @param \NitroPack\NitroPack\Api\TaggingServiceInterface $tagger
     * @param \Magento\Cms\Api\BlockRepositoryInterface $blockRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(Config                                           $config, Registry $registry,
                                \NitroPack\NitroPack\Api\TaggingServiceInterface $tagger,
                                \Magento\Cms\Api\BlockRepositoryInterface        $blockRepository,
                                \Magento\Framework\Api\SearchCriteriaBuilder     $searchCriteriaBuilder

    )
    {
        $this->blockRepository = $blockRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->config = $config;
        $this->tagger = $tagger;
        $this->registry = $registry;
    }

    /**
     * Perform result postprocessing
     *
     * @param ResultInterface $subject
     * @param ResultInterface $result
     * @param ResponseHttp $response
     * @return ResultInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRenderResult(ResultInterface $subject, ResultInterface $result, ResponseHttp $response)
    {
        $usePlugin = $this->registry->registry('use_page_cache_plugin');

        if ($this->config->getType() != NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE) {
            return $result;
        }
        $tagsHeader = $response->getHeader('X-Magento-Tags');
        $tags = [];
        $blockTags = [];
        $blockTagsIds = [];
        if ($tagsHeader) {
            $tags = explode(',', $tagsHeader->getFieldValue());
            $response->clearHeader('X-Magento-Tags');
        }
        foreach ($tags as $tagsValue) {

            if (strpos($tagsValue, 'cms_b_') !== false) {
                //CMS Block tagger
                $tagsValueBlock = str_replace('cms_b_', '', $tagsValue);
                if (!empty($tagsValueBlock) && is_numeric($tagsValueBlock)) {
                    $blockTagsIds[] = $tagsValueBlock;
                } else {
                    $blockTags[] = $tagsValueBlock;
                }
            }else{
                $this->tagger->addTag($tagsValue);
            }
        }

        if (count($blockTagsIds) == 0 && count($blockTags) > 0) {

            $searchCriteria = $this->searchCriteriaBuilder->addFilter('identifier', $blockTags, 'in')->create();
            $cmsBlocks = $this->blockRepository->getList($searchCriteria)->getItems();
            foreach ($cmsBlocks as $cmsBlock) {

                //$this->blockTagger($cmsBlock->getId());
                $this->tagger->addTag('cms_b_'.$cmsBlock->getId());
            }
        } else {
            foreach ($blockTagsIds as $blockTagsId)
                //$this->blockTagger($blockTagsId);
            $this->tagger->addTag('cms_b_'.$blockTagsId);
        }
        $tags = array_unique(array_merge($tags, [CacheType::CACHE_TAG]));
        $tags = implode(',', $tags);
        $response->setHeader('X-Magento-Tags', $tags);

        return $result;
    }


    public function productTagger($productValue)
    {
        $product = str_replace('cat_p_', '', $productValue);
        if (!empty($product)) {
            $this->tagger->tagProduct((int)$product);
        }
    }

    public function categoryTagger($categoryValue)
    {
        $category = str_replace('cat_c_', '', $categoryValue);
        if (!empty($category)) {
            $this->tagger->tagCategory((int)$category);
        }
    }

    public function blockTagger($block)
    {
        $this->tagger->tagBlock((int)$block);

    }

}

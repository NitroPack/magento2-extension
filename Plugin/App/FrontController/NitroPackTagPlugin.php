<?php

namespace NitroPack\NitroPack\Plugin\App\FrontController;


use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\PageCache\Model\Cache\Type as CacheType;
use Magento\PageCache\Model\Config;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\FastlyHelper;

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
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @param FastlyHelper
     * */
    protected $fastlyHelper;
    /**
     * @param Config $config
     * @param Registry $registry
     * @param \NitroPack\NitroPack\Api\TaggingServiceInterface $tagger
     * @param FastlyHelper $fastlyHelper
     * @param \Magento\Cms\Api\BlockRepositoryInterface $blockRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $_scopeConfig,
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Config                                           $config,
        Registry                                         $registry,
        \NitroPack\NitroPack\Api\TaggingServiceInterface $tagger,
        FastlyHelper $fastlyHelper,
        \Magento\Cms\Api\BlockRepositoryInterface        $blockRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $_scopeConfig,
        \Magento\Framework\Api\SearchCriteriaBuilder     $searchCriteriaBuilder

    )
    {
        $this->blockRepository = $blockRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->fastlyHelper = $fastlyHelper;
        $this->config = $config;
        $this->tagger = $tagger;
        $this->_scopeConfig = $_scopeConfig;
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

        if (!in_array($this->_scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK), [NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE, NitroService::FASTLY_CACHING_APPLICATION_VALUE])) {
            return $result;
        }

        //Check NitroPack With Fastly Disable
        if ($this->fastlyHelper->isFastlyAndNitroDisable()){
            return $result;
        }

        $tagsHeader = $response->getHeader('X-Magento-Tags');
        $tags = [];
        $blockTags = [];
        $blockTagsIds = [];
        if ($tagsHeader) {
            $tagValue = $tagsHeader->getFieldValue();
            if ($this->_scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK) ==NitroService::FASTLY_CACHING_APPLICATION_VALUE && $this->_scopeConfig->getValue(NitroService::XML_FASTLY_PAGECACHE_ENABLE_NITRO)) {
                $tagValue = $this->revertTagsForNitroPackDueToFastly($tagValue);
                $tagValue = str_replace(' ', ',', $tagValue);
            }
            $tags = explode(',', $tagValue);
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
            } else {
                $this->tagger->addTag($tagsValue);
            }
        }

        if (count($blockTagsIds) == 0 && count($blockTags) > 0) {

            $searchCriteria = $this->searchCriteriaBuilder->addFilter('identifier', $blockTags, 'in')->create();
            $cmsBlocks = $this->blockRepository->getList($searchCriteria)->getItems();
            foreach ($cmsBlocks as $cmsBlock) {

                //$this->blockTagger($cmsBlock->getId());
                $this->tagger->addTag('cms_b_' . $cmsBlock->getId());
            }
        } else {
            foreach ($blockTagsIds as $blockTagsId)
                //$this->blockTagger($blockTagsId);
                $this->tagger->addTag('cms_b_' . $blockTagsId);
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

    function revertTagsForNitroPackDueToFastly($tags)
    {
        $patterns = array('/\b(p)\b/','/\b(c)\b/','/\b(cb)\b/','/\b(cpg)\b/','/p(\d+)/','/c(\d+)/','/\b(cb)/','/\b(cpg)/','/\b(cp)/');
        $replacements = array('cat_p', 'cat_c','cms_b','cms_p','cat_p_$1','cat_c_$1','cms_b','cms_p','cms_p');
        return preg_replace($patterns, $replacements, $tags);

    }
}

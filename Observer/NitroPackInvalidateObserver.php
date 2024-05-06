<?php

namespace NitroPack\NitroPack\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Cache\Tag\Resolver;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\PageFactory;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Helper\ApiHelper;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use NitroPack\NitroPack\Helper\FastlyHelper;

class NitroPackInvalidateObserver implements ObserverInterface
{
    /**
     * @var Resolver
     * */
    protected $cacheTagResolver;
    /**
     * @var \Magento\Framework\MessageQueue\DefaultValueProvider
     * */
    protected $defaultQueueValueProvider;
    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     * */
    protected $publisher;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     * */
    protected $json;
    /**
     * @var DeploymentConfig
     * */
    protected $config;
    protected $defaultQueueValueConnection = null;

    protected $storeId = null;

    const TOPIC_NAME_AMQP = 'nitropack.cache.queue.topic';
    const TOPIC_NAME_DB = 'nitropack.cache.queue.topic.db';

    protected $storeManager;
    /**
     * @var ProductFactory
     **/
    protected $productFactory;
    /**
     * @var PageFactory
     **/
    protected $pageFactory;
    /**
     * @var BlockFactory
     **/
    protected $blockFactory;
    /**
     * @var CategoryFactory
     **/
    protected $categoryFactory;
    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     * */
    protected $cacheTypeList;
    /**
     * @var \Magento\Framework\App\Cache\Frontend\Pool
     * */
    protected $cacheFrontendPool;
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;
    /**
     * @var ApiHelper
     * */
    protected $apiHelper;
    /**
     * @var CollectionFactory
     * */
    protected $attributeCollectionFactory;
    /**
     * @var \Magento\Store\Api\GroupRepositoryInterface
     * */
    protected $storeRepo;
    /**
     * @var FastlyHelper
     * */
    protected $fastlyHelper;

    /**
     * @param Resolver $cacheTagResolver
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param StoreManagerInterface $storeManager
     * @param ProductFactory $productFactory
     * @param BlockFactory $blockFactory
     * @param CategoryFactory $categoryFactory
     * @param PageFactory $pageFactory
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * @param ScopeConfigInterface $_scopeConfig
     * @param ApiHelper $apiHelper
     * @param FastlyHelper $fastlyHelper
     * @param CollectionFactory $attributeCollectionFactory
     * @param \Magento\Store\Api\GroupRepositoryInterface $storeRepo
     * @param DeploymentConfig $config
     */
    public function __construct(
        Resolver                                           $cacheTagResolver,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\Framework\Serialize\Serializer\Json       $json,
        StoreManagerInterface                              $storeManager,
        ProductFactory                                     $productFactory,
        BlockFactory                                       $blockFactory,
        CategoryFactory                                    $categoryFactory,
        PageFactory                                        $pageFactory,
        \Magento\Framework\App\Cache\TypeListInterface     $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool         $cacheFrontendPool,
        ScopeConfigInterface                               $_scopeConfig,
        ApiHelper                                          $apiHelper,
        FastlyHelper                                       $fastlyHelper,
        CollectionFactory                                  $attributeCollectionFactory,
        \Magento\Store\Api\GroupRepositoryInterface        $storeRepo,
        DeploymentConfig                                   $config
    )
    {
        $this->fastlyHelper = $fastlyHelper;
        $this->apiHelper = $apiHelper;
        $this->_scopeConfig = $_scopeConfig;
        $this->cacheTagResolver = $cacheTagResolver;
        $this->config = $config;
        $this->storeRepo = $storeRepo;
        $this->json = $json;
        $this->storeManager = $storeManager;
        $this->publisher = $publisher;
        $this->pageFactory = $pageFactory;
        $this->categoryFactory = $categoryFactory;
        $this->blockFactory = $blockFactory;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->productFactory = $productFactory;
    }

    public function execute(Observer $observer)
    {

        if (!$this->isEnabled()) {
            return false;
        }

        $object = $observer->getEvent()->getObject();

        if (!$object instanceof \Magento\Framework\DataObject\IdentityInterface) {
            return false;
        }

        $tags = $this->cacheTagResolver->getTags($object);

        if (!empty($tags) && count($tags)) {

            if ($object instanceof \Magento\Catalog\Model\Product\Interceptor) {
                $skipAttributeValue = [];
                $skipAttribute = $this->getNitroPackCacheSkipAttribute();
                if (count($skipAttribute) != 0) {
                    $skipAttributeValue = array_column($skipAttribute['items'], 'attribute_code');

                }
                if (!$this->checkProductChanges($object, $skipAttributeValue)) {
                    return false;
                }
            }

            $stores = $this->storeRepo->getList();
            foreach ($stores as $storesData) {
                $storeId = $storesData->getDefaultStoreId();
                if ($storeId > 0) {
                    $settings = $this->getSettings($storesData);
                    if ($settings) {
                        if (isset($settings->enabled) && $settings->enabled) {
                            //Check NitroPack With Fastly Disable
                            if ($this->fastlyHelper->isFastlyAndNitroDisable()) {
                                return false;
                            }

                            $tags = $this->processIgnoredTags($tags);

                            //Non-Zero Product Quantity Check
                            $this->nonZeroProductQuantityCheck($object, $settings);

                            foreach ($tags as $tag) {
                                $this->invalidateTag($tag, $storeId);
                            }
                        }
                    }
                }
            }
        }
    }

    public function getTopicName()
    {
        return $this->defaultQueueValueConnection == 'amqp' && $this->config->get('queue/amqp') && count($this->config->get('queue/amqp')) > 0 ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB;
    }

    public function patternIgnoreTag($tags, $patternsIgnoreTag)
    {
        $readyTags = [];
        // Iterate through each tag
        foreach ($tags as $tag) {
            $ignore = false; // Flag to determine if tag should be ignored

            // Check against each pattern
            foreach ($patternsIgnoreTag as $pattern) {
                // Escape special characters and convert * to .*
                $pattern = preg_quote($pattern, '/');
                $pattern = str_replace('\*', '.*', $pattern);

                // Add start and end anchors to match the whole string
                $pattern = '/^' . $pattern . '$/';

                // Check if tag matches the pattern
                if (preg_match($pattern, $tag)) {
                    $ignore = true; // Set flag to ignore the tag
                    break; // No need to check further patterns
                }
            }

            // Process or ignore the tag based on the flag
            if (!$ignore) {
                $readyTags[] = $tag;
            }


        }
        return $readyTags;

    }

    public function getTagTypeAndId($cacheTag)
    {
        $id = false;
        switch (true) {
            case strpos($cacheTag, "cat_p") !== false:
                $tagType = 'product';
                $id = (int)str_replace('cat_p_', '', $cacheTag);
                break;
            case strpos($cacheTag, "cat_c_p") !== false:
                $tagType = 'category_product_page';
                $id = (int)str_replace('cat_c_p', '', $cacheTag);
                break;
            case strpos($cacheTag, "cat_c_") !== false:
                $tagType = 'category';
                $id = (int)str_replace('cat_c_', '', $cacheTag);
                break;
            case strpos($cacheTag, "cms_b") !== false:
                $tagType = 'block';
                $id = (int)str_replace('cms_b_', '', $cacheTag);
                break;

            case strpos($cacheTag, "cms_p_") !== false:
                $tagType = 'page';
                $id = (int)str_replace('cms_p_', '', $cacheTag);
                break;
            default:
                $tagType = "";
                $id = false;
                break;
        }
        return [$tagType, $id];
    }

    public function getReasonFromType($tagType, $id)
    {

        if ($tagType == 'product' && $id) {
            $product = $this->productFactory->create()->load($id);
            return $product;
        }
        if ($tagType == 'category' && $id) {
            $category = $this->categoryFactory->create()->load($id);
            return $category;
        }
        if ($tagType == 'block' && $id) {
            $block = $this->blockFactory->create()->load($id);
            return $block;
        }
        if ($tagType == 'page' && $id) {
            $page = $this->pageFactory->create()->load($id);
            return $page;
        }
        return '';
    }

    /**
     * @param $reasonEntity
     * @param int $storeId
     * @param string $reasonEntityName
     * @param array $rawData
     * @return array
     */
    public function categoryProductInvalidate($reasonEntity, int $storeId, string $reasonEntityName, array $rawData): array
    {
        foreach ($reasonEntity->getCategoryIds() as $catId) {
            if (in_array('cat_c_' . $catId, $this->getIgnoreTags())) {
                continue;
            }
            //Check Category Pattern is Defined
            $patternTagsArray = array_filter($this->getIgnoreTags(), function ($value) {
                return strpos($value, 'cat_c_*') !== false;
            });
            if (count($patternTagsArray) > 0) {
                continue;
            }
            $rawData = [
                'action' => 'invalidation',
                'type' => 'category',
                'tag' => 'cat_c_' . $catId,
                'reasonType' => 'category for',
                'storeId' => $storeId,
                'reasonEntity' => $reasonEntityName
            ];

            $this->publisher->publish($this->getTopicName(), $this->json->serialize($rawData));
        }
        return $rawData;
    }


    private function filterArrayByKeys(array $input, array $column_keys)
    {
        $result = array();
        $column_keys = array_flip($column_keys); // getting keys as values
        foreach ($input as $key => $val) {
            // getting only those key value pairs, which matches $column_keys
            $result[$key] = array_intersect_key($val, $column_keys);
        }
        return $result;
    }

    /**
     * @param $object
     * @return bool
     */
    public function checkProductChanges($object, $skipAttributeValue)
    {

        $originalData = $object->getOrigData();
        $storeData = $object->getData();
        $diff = [];
        $skipAttr = ['updated_at'];
        $skipAttr = array_merge($skipAttributeValue, $skipAttr);

        foreach ($object->getData() as $key => $value) {
            if ($key == 'media_gallery') {

                if (isset($object->getOrigData($key)['images']) && isset($object->getData($key)['images'])) {
                    if ($this->filterArrayByKeys($object->getOrigData($key)['images'], ['file', 'media_type', 'label', 'position', 'disabled', 'label_default', 'disabled_default']) == $this->filterArrayByKeys($object->getData($key)['images'], ['file', 'media_type', 'label', 'position', 'disabled', 'label_default', 'disabled_default'])) {
                        continue;
                    }
                }
            }
            if ($object->hasData($key) && !is_null($object->getOrigData($key)) && !in_array($key, $skipAttr) && $object->getOrigData($key) != $value) {
                if ($key == 'quantity_and_stock_status' && isset($originalData['quantity_and_stock_status']) && isset($originalData['quantity_and_stock_status']['is_in_stock']) && isset($storeData['quantity_and_stock_status']) && isset($storeData['quantity_and_stock_status']['is_in_stock']) && (bool)$originalData['quantity_and_stock_status']['is_in_stock'] == (bool)$storeData['quantity_and_stock_status']['is_in_stock']) {
                    continue;
                }
                if ($key == 'website_ids' && isset($originalData['website_ids']) && isset($storeData['website_ids']) && (bool)array_values($originalData['website_ids']) == $storeData['website_ids']) {
                    continue;
                }


                $diff[$key] = [
                    $key . '_set' => $value,
                    $key . '_original' => $object->getOrigData($key),
                ];

            }
        }


        if (count($diff) == 0) {
            return false;
        }
        return true;
    }

    /**
     * @param $object
     * @return bool
     */
    public function checkNonZeroQtyChanges($object)
    {
        $originalData = $object->getOrigData();
        $saveCatalogInventoryData = $object->getData();

        if (isset($originalData['qty'])) {

            $newQty = (int)$saveCatalogInventoryData['qty'] = $saveCatalogInventoryData['qty'] ?? 0;
            $oldQty = (int)$originalData['qty'] = $saveCatalogInventoryData['qty'] ?? 0;

            if ($newQty == 0 && $newQty != $oldQty) {
                return false;

            }
            if ($oldQty == 0 && $newQty > 0) {
                return false;

            }
        }
        return true;
    }

    public function getNitroPackCacheSkipAttribute()
    {
        try {
            $attributeCollection = $this->attributeCollectionFactory->create();
            $attributeCollection
                ->addFieldToFilter('nitro_purge', 0); // Additional filter for layered navigation

            return $attributeCollection->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }


    public function getIgnoreTags()
    {

        $ignoreTags = $this->_scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK_IGNORE_TAGS);
        if (!is_null($ignoreTags) && $ignoreTags) {
            $ignoreTags = explode(',', $ignoreTags);
            $ignoreTags = array_map('trim', $ignoreTags);
            $ignoreTags = array_filter($ignoreTags);
            $ignoreTags = array_unique($ignoreTags);
            return $ignoreTags;
        }

        return [];
    }

    /**
     * @param $object
     * @param $settings
     * @return false|void
     */
    private function nonZeroProductQuantityCheck($object, $settings)
    {
        if ($object instanceof \Magento\CatalogInventory\Model\Adminhtml\Stock\Item\Interceptor) {
            if (isset($settings->default_stock) && $settings->default_stock && $this->checkNonZeroQtyChanges($object)) {
                return false;
            }
        }
    }

    /**
     * @param $tag
     * @param $storeId
     * @return void
     */
    private function invalidateTag($tag, $storeId)
    {
        list($tagType, $id) = $this->getTagTypeAndId($tag);
        $reasonEntity = $this->getReasonFromType($tagType, $id);
        $reasonEntityName = "";
        if (!empty($reasonEntity)) {
            $reasonEntityName = $reasonEntity->getName();
        }
        if (strpos($tagType, 'category_product_page') !== false || strpos($tagType, 'page') !== false) {
            $rawData = [
                'action' => 'purge_tag',
                'type' => $tagType,
                'tag' => $tag,
                'reasonType' => $tagType,
                'storeId' => $storeId,
                'reasonEntity' => $reasonEntityName
            ];
            $this->publisher->publish($this->getTopicName(), $this->json->serialize($rawData));

        } else {
            $rawData = [
                'action' => 'invalidation',
                'type' => $tagType,
                'tag' => $tag,
                'reasonType' => $tagType,
                'storeId' => $storeId,
                'reasonEntity' => $reasonEntityName
            ];

            $this->publisher->publish($this->getTopicName(), $this->json->serialize($rawData));
            if ($tagType == 'product' && !empty($reasonEntity) && $reasonEntity->getId()) {
                $this->categoryProductInvalidate($reasonEntity, $storeId, $reasonEntityName, $rawData);
                $this->cacheTypeList->cleanType('collections');
                foreach ($this->cacheFrontendPool as $cacheFrontend) {
                    $cacheFrontend->getBackend()->clean();
                }
            }
        }
    }

    /**
     * @param $storesData
     * @return mixed|null
     */
    private function getSettings($storesData)
    {
        $settingsFilename = $this->apiHelper->getSettingsFilename($storesData->getCode());
        $data = $this->apiHelper->readFile($settingsFilename);
        //Check The file is readable
        if ($data) {
            try {
                return json_decode($data);
            } catch (\Exception $exception) {

            }
        }

        return null;
    }

    /**
     * @param $tags
     * @return array
     */
    private function processIgnoredTags($tags): array
    {
        $tags = array_unique($tags);
        $tags = array_diff($tags, $this->getIgnoreTags());
        $patternTagsArray = array_filter($this->getIgnoreTags(), function ($value) {
            return strpos($value, '_*') !== false;
        });

        if (count($patternTagsArray) > 0) {
            $tags = $this->patternIgnoreTag($tags, $patternTagsArray);
        }

        return $tags;
    }

    /**
     * @return bool
     */
    private function isEnabled(): bool
    {
        return !is_null($this->_scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK)) && in_array(
                $this->_scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK),
                [NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE, NitroService::FASTLY_CACHING_APPLICATION_VALUE]
            );
    }

}

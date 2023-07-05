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
use NitroPack\NitroPack\Helper\ApiHelper;

class CleanCacheByTagsObserver implements ObserverInterface
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
        ApiHelper $apiHelper,
        DeploymentConfig                                   $config
    )
    {
        $this->apiHelper = $apiHelper;
        $this->_scopeConfig = $_scopeConfig;
        $this->cacheTagResolver = $cacheTagResolver;
        $this->config = $config;
        $this->json = $json;
        $this->storeManager = $storeManager;
        $this->publisher = $publisher;
        $this->pageFactory = $pageFactory;
        $this->categoryFactory = $categoryFactory;
        $this->blockFactory = $blockFactory;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->productFactory = $productFactory;
    }

    public function execute(Observer $observer)
    {

        if (!is_null(
                $this->_scopeConfig->getValue(\NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK)
            ) && $this->_scopeConfig->getValue(
                \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK
            ) == \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE) {

            $object = $observer->getEvent()->getObject();
            if (!is_object($object)) {
                return;
            }
            $tags = $this->cacheTagResolver->getTags($object);
            if (!empty($tags) && count($tags)) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $storeRepo = $objectManager->create(\Magento\Store\Api\GroupRepositoryInterface::class);
                $stores = $storeRepo->getList();
                foreach ($stores as $storesData) {
                    $storeId = $storesData->getDefaultStoreId();
                    if ($storeId > 0) {
                        $settingsFilename = $this->apiHelper->getSettingsFilename($storesData->getCode());
                        $haveData = $this->apiHelper->readFile($settingsFilename);
                        //Check The file is readable
                        if ($haveData) {
                            $settings = json_decode($haveData);
                            if (isset($settings->enabled) && $settings->enabled) {
                                $tags = array_unique($tags);
                                foreach ($tags as $tag) {
                                    list($tagType, $id) = $this->getTagTypeAndId($tag);

                                    $reasonEntity = $this->getReasonFromType($tagType, $id);
                                    $reasonEntityName = "";

                                    if (!empty($reasonEntity)) {
                                        $reasonEntityName = $reasonEntity->getName();
                                    }
                                    if (strpos($tagType,'category_product_page') !==false  || strpos($tagType,'page') !==false ) {
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

}

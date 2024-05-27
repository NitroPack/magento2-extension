<?php

namespace NitroPack\NitroPack\Model\Purge;

use Exception;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\Tag\Resolver;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\PurgeManagementInterface;
use NitroPack\NitroPack\Helper\ApiHelper;
use NitroPack\NitroPack\Helper\FastlyHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use NitroPack\SDK\NitroPack;
use Magento\Framework\Filesystem\DirectoryList;

class PurgeManagement implements PurgeManagementInterface
{
    /**
     * @var Resolver
     * */
    protected $cacheTagResolver;

    protected $storeId = null;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var TypeListInterface
     * */
    protected $cacheTypeList;
    /**
     * @var Pool
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
     * @var GroupRepositoryInterface
     * */
    protected $storeRepo;
    /**
     * @var FastlyHelper
     * */
    protected $fastlyHelper;
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;
    /**
     * @var DirectoryList
     */
    private $directoryList;

    private $sdk = null;

    /**
     * @param Resolver $cacheTagResolver
     * @param StoreManagerInterface $storeManager
     * @param TypeListInterface $cacheTypeList
     * @param Pool $cacheFrontendPool
     * @param ScopeConfigInterface $_scopeConfig
     * @param ApiHelper $apiHelper
     * @param FastlyHelper $fastlyHelper
     * @param GroupRepositoryInterface $storeRepo
     * @param DirectoryList $directoryList
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        Resolver                    $cacheTagResolver,
        StoreManagerInterface       $storeManager,
        TypeListInterface           $cacheTypeList,
        Pool                        $cacheFrontendPool,
        ScopeConfigInterface        $_scopeConfig,
        ApiHelper                   $apiHelper,
        FastlyHelper                $fastlyHelper,
        GroupRepositoryInterface    $storeRepo,
        DirectoryList               $directoryList,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository
    )
    {
        $this->fastlyHelper = $fastlyHelper;
        $this->apiHelper = $apiHelper;
        $this->_scopeConfig = $_scopeConfig;
        $this->cacheTagResolver = $cacheTagResolver;
        $this->storeRepo = $storeRepo;
        $this->storeManager = $storeManager;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->directoryList = $directoryList;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param $productIds
     * @return void
     * @throws NoSuchEntityException
     */
    public function purgeByProductIds($productIds)
    {
        foreach ($productIds as $productId) {
            $product = $this->productRepository->getById($productId);
            $tags = $this->cacheTagResolver->getTags($product);
            $this->purgeByCacheTags($tags);
        }
    }

    /**
     * @param $categoryIds
     * @return void
     * @throws NoSuchEntityException
     */
    public function purgeBycategoryIds($categoryIds)
    {
        foreach ($categoryIds as $categoryId) {
            $category = $this->categoryRepository->get($categoryId);
            $tags = $this->cacheTagResolver->getTags($category);
            $this->purgeByCacheTags($tags);
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
            } catch (Exception $exception) {

            }
        }

        return null;
    }

    /**
     * @param $urls
     * @return bool
     * @throws Exception
     */
    public function purgeByUrl($urls)
    {
        if (!empty($urls) && count($urls)) {
            $stores = $this->storeRepo->getList();
            foreach ($stores as $storesData) {
                $storeId = $storesData->getDefaultStoreId();
                if ($storeId > 0) {
                    $settings = $this->getSettings($storesData);
                    if ($settings) {
                        if (isset($settings->enabled) && $settings->enabled) {

                            if ($this->fastlyHelper->isFastlyAndNitroDisable()) {
                                return false;
                            }

                            $cachePath = $this->getCachePath($settings);

                            $this->sdk = new NitroPack(
                                $settings->siteId, $settings->siteSecret, null, null, $cachePath
                            );

                            foreach ($urls as $url) {
                                $this->sdk->purgeCache(
                                    $url,
                                    null,
                                    \NitroPack\SDK\PurgeType::COMPLETE,
                                    "Purge Url from command line"
                                );
                            }
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function purgeAll()
    {
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

                        $cachePath = $this->getCachePath($settings);

                        $this->sdk = new NitroPack(
                            $settings->siteId, $settings->siteSecret, null, null, $cachePath
                        );

                        $this->sdk->purgeCache(
                            null,
                            null,
                            \NitroPack\SDK\PurgeType::COMPLETE,
                            "Purge All from command line"
                        );

                    }
                }
            }
        }

        return true;
    }

    /**
     * @param $settings
     * @return string
     */
    public function getCachePath($settings)
    {
        $rootPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
        try {
            $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
        } catch (FileSystemException $e) {
            // fallback to using the module directory
        }

        return $rootPath . 'nitro_cache' . DIRECTORY_SEPARATOR . $settings->siteId;
    }

    /**
     * @param $tags
     * @return bool
     * @throws Exception
     */
    public function purgeByCacheTags($tags)
    {
        if (!empty($tags) && count($tags)) {
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

                            $cachePath = $this->getCachePath($settings);

                            $this->sdk = new NitroPack(
                                $settings->siteId, $settings->siteSecret, null, null, $cachePath
                            );

                            foreach ($tags as $tag) {
                                $this->sdk->purgeCache(
                                    null,
                                    $tag,
                                    \NitroPack\SDK\PurgeType::COMPLETE,
                                    "Purge from command line"
                                );
                            }
                        }
                    }
                }
            }
        }
        return true;
    }
}

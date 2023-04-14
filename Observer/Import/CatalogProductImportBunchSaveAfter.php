<?php

namespace NitroPack\NitroPack\Observer\Import;


use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Api\TaggingServiceInterface;
use NitroPack\NitroPack\Observer\CacheClearObserver;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class CatalogProductImportBunchSaveAfter implements ObserverInterface
{

    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;

    /**
     * @var TaggingServiceInterface
     * */
    protected $tagger;
    /**
     * @var RequestInterface
     * */
    protected $request;
    /**
     * @var StoreManagerInterface
     * */
    protected $storeManager;
    /**
     * @var LoggerInterface
     * */
    protected $logger;

    /**
     * @var ProductRepositoryInterface
     * */
    protected $productRepository;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $_json;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $_publisher;

    const TOPIC_NAME_AMQP = 'nitropack.cache.queue.topic';
    const TOPIC_NAME_DB = 'nitropack.cache.queue.topic.db';


    protected $storeId = 0;
    /**
     * @var \Magento\Framework\MessageQueue\DefaultValueProvider
     * */
    protected $defaultQueueValueProvider;

    protected $defaultQueueValueConnection;

    public function __construct(
        NitroServiceInterface                                $nitro,
        TaggingServiceInterface                              $tagger,
        RequestInterface                                     $request,
        StoreManagerInterface                                $storeManager,
        \Magento\Framework\MessageQueue\PublisherInterface   $publisher,
        \Magento\Framework\Serialize\Serializer\Json         $json,
        \Magento\Framework\MessageQueue\DefaultValueProvider $defaultQueueValueProvider,
        ProductRepositoryInterface                           $productRepository,
        LoggerInterface                                      $logger
    )
    {
        $this->logger = $logger;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->nitro = $nitro;
        $this->tagger = $tagger;
        $this->_json = $json;
        $this->_publisher = $publisher;
        $this->defaultQueueValueConnection = $defaultQueueValueProvider->getConnection();
        $this->storeId = $this->request->getParam('store');
        if ($this->storeId == 0) {
            $this->storeId = $this->storeManager->getDefaultStoreView()->getId();
        }
        $store = $this->storeManager->getStore($this->storeId);
        $this->nitro->reload($this->storeManager->getGroup($store->getStoreGroupId())->getCode());
    }

    public function execute(Observer $observer)
    {
        $eventName = $observer->getEvent()->getName();
        try {
            $bunch = $observer->getBunch();
            foreach ($bunch as $product) {
                $product = $this->productRepository->get($product['sku']);

                $this->productSave($product);
            }
        } catch (\Execption $e) {
            $this->logger->info($e->getMessage());
        }
    }


    public function productSave($product)
    {
        $tag = $this->tagger->getProductTag($product);
        $productName = $product->getName();
        if (!$productName || $productName == '') {
            $productName = '#' . $product->getId();
        }
        $rawData = [
            'action' => 'invalidation',
            'type' => 'product',
            'tag' => $tag,
            'reasonType' => 'product',
            'storeId' => $this->storeId,
            'reasonEntity' => $productName
        ];

        $this->_publisher->publish($this->defaultQueueValueConnection == 'amqp' ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB, $this->_json->serialize($rawData));
        //$this->invalidateTag($tag, 'product', $productName);

        foreach ($product->getCategoryIds() as $catId) {
            $cTag = $this->tagger->getCategoryTag(intval($catId));
            if ($cTag) {
                $rawData = [
                    'action' => 'invalidation',
                    'type' => 'category',
                    'tag' => $cTag,
                    'reasonType' => 'category for',
                    'storeId' => $this->storeId,
                    'reasonEntity' => $productName
                ];
                $this->_publisher->publish($this->defaultQueueValueConnection == 'amqp' ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB, $this->_json->serialize($rawData));
                // $this->invalidateTag($cTag, 'category for', $productName);
            }
        }
    }

}

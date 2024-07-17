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
namespace NitroPack\NitroPack\Observer\Import;


use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Api\TaggingServiceInterface;
use NitroPack\NitroPack\Observer\DeleteObserver;
use NitroPack\NitroPack\Logger\Logger;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Class CatalogProductImportBunchSaveAfter - Product invalidation and cache purge observer when importing products in bulk
 * @implements ObserverInterface
 * @package NitroPack\NitroPack\Observer\Import
 * @since 2.0.0
 * */
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
     * @var Logger
     */
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
        Logger                                               $logger
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
        } catch (\Exception $e) {
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

<?php

namespace NitroPack\NitroPack\Observer\CacheClear;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Review\Model\Review;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;

class ReviewDeletedObserver implements ObserverInterface
{
    protected $storeId = 0;
    /**
     * @var RequestInterface
     * */
    protected $request;
    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     * */
    protected $publisher;
    /**
     * @var StoreManagerInterface
     * */
    protected $storeManager;
    /**
     * @var \Magento\Framework\MessageQueue\DefaultValueProvider
     * */
    protected $defaultQueueValueProvider;
    /**
     * @var DeploymentConfig
     * */
    protected $config;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     * */
    protected $json;
    protected $defaultQueueValueConnection = null;

    const TOPIC_NAME_AMQP = 'nitropack.cache.queue.topic';
    const TOPIC_NAME_DB = 'nitropack.cache.queue.topic.db';
    /**
     * @var ObserverInterface
     * */
    protected $objectManager;
    /**
     * @param RequestInterface   $request
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\MessageQueue\PublisherInterface   $publisher
     * @param \Magento\Framework\MessageQueue\DefaultValueProvider $defaultQueueValueProvider
     * @param \Magento\Framework\Serialize\Serializer\Json         $json
     * @param DeploymentConfig                                     $config
     * */
    public function __construct(
        RequestInterface                                     $request,
        StoreManagerInterface                                $storeManager,
        \Magento\Framework\MessageQueue\PublisherInterface   $publisher,
        \Magento\Framework\MessageQueue\DefaultValueProvider $defaultQueueValueProvider,
        \Magento\Framework\Serialize\Serializer\Json         $json,
        DeploymentConfig                                     $config
    )
    {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->request = $request;
        $this->publisher = $publisher;
        $this->storeManager = $storeManager;
        $this->storeId = $this->request->getParam('store');
        $this->defaultQueueValueProvider = $defaultQueueValueProvider;
        $this->defaultQueueValueProvider->getConnection();
        $this->config = $config;
        $this->json = $json;
        if ($this->storeId == 0) {
            $this->storeId = $this->storeManager->getDefaultStoreView()->getId();
        }
    }
    protected $review; // Magento\Catalog\Model\Product

    public function execute(Observer $observer)
    {
        $data = $observer->getEvent()->getData();
        if (!isset($data['object']) && is_a($data['object'], Review::class)) {
            return false;
        }
        $this->review = $data['object'];
        $productId = $this->review->getEntityPkValue();
        $product = $this->objectManager->create(Product::class)->load($productId);
        $productName = $product->getName();
        if (!$productName || $productName == '') {
            $productName = '#' . $product->getId();
        }

        $productName .= ' (review)';
        $rawData = [
            'action' => 'purge_tag',
            'type' => 'product',
            'tag' => 'cat_p_'.$product->getId(),
            'reasonType' => 'product',
            'storeId' => $this->storeId,
            'reasonEntity' => $productName
        ];
        $this->publisher->publish($this->getTopicName(), $this->json->serialize($rawData));

    }

    public function getTopicName()
    {
        return $this->defaultQueueValueConnection == 'amqp' && $this->config->get('queue/amqp') && count($this->config->get('queue/amqp')) > 0 ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB;
    }

}

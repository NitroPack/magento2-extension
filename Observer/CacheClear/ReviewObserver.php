<?php

namespace NitroPack\NitroPack\Observer\CacheClear;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Review\Model\Review;
use Magento\Catalog\Model\Product;

use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Api\TaggingServiceInterface;
use NitroPack\NitroPack\Observer\CacheClearObserver;
use Psr\Log\LoggerInterface;

class ReviewObserver extends CacheClearObserver
{
    protected static $eventMap = array(
        'review_save_commit_after' => 'purgeProductPageCache',
        'review_delete_commit_after' => 'purgeProductPageCache'
    );

    protected $review; // Magento\Review\Model\Review
    protected $storeId = 0;

    public function __construct(
        NitroServiceInterface $nitro,
        TaggingServiceInterface $tagger,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\Framework\MessageQueue\DefaultValueProvider $defaultQueueValueProvider,
        \Magento\Framework\Serialize\Serializer\Json $json,
        LoggerInterface $logger
    ) {
        parent::__construct($nitro, $tagger, $request, $storeManager, $logger,$defaultQueueValueProvider, $publisher, $json);
        $this->storeId = $this->request->getParam('store');
        if ($this->storeId == 0) {
            $this->storeId = $this->storeManager->getDefaultStoreView()->getId();
        }
    }

    public function prepareData(Observer $observer)
    {
        if (!$this->shouldAutoClear('reviews')) {
            return false;
        }

        $data = $observer->getEvent()->getData();

        if (!isset($data['object']) && is_a($data['object'], Review::class)) {
            return false;
        }

        $this->review = $data['object'];

        return true;
    }

    public function purgeProductPageCache(Observer $observer)
    {
        $productId = $this->review->getEntityPkValue();
        $product = $this->objectManager->create(Product::class)->load($productId);

        $tag = $this->tagger->getProductTag($product);

        $productName = $product->getName();
        if (!$productName || $productName == '') {
            $productName = '#' . $product->getId();
        }

        $productName .= ' (review)';
        $rawData = [
            'action' => 'purge_tag',
            'type' => 'product',
            'tag' => $tag,
            'reasonType' => 'product',
            'storeId' => $this->storeId,
            'reasonEntity' => $productName
        ];
        $this->_publisher->publish($this->defaultQueueValueConnection =='amqp' ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB, $this->_json->serialize($rawData));
        // $this->purgeTagPageCache($tag, 'product', $productName);
    }

}

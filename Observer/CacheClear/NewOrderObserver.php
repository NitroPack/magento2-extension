<?php

namespace NitroPack\NitroPack\Observer\CacheClear;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;

use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Api\TaggingServiceInterface;
use NitroPack\NitroPack\Observer\CacheClearObserver;
use Psr\Log\LoggerInterface;

class NewOrderObserver extends CacheClearObserver
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $_json;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $_publisher;

    const TOPIC_NAME = 'nitropack.cache.queue.topic';
    protected $storeId = 0;

    protected static $eventMap = array(
        'sales_order_save_after' => 'newOrder'
    );

    public function __construct(
        NitroServiceInterface $nitro,
        TaggingServiceInterface $tagger,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\Framework\Serialize\Serializer\Json $json,
        LoggerInterface $logger
    ) {
        parent::__construct($nitro, $tagger, $request, $storeManager, $logger, $publisher, $json);
        $this->storeId = $this->request->getParam('store');
        if ($this->storeId == 0) {
            $this->storeId = $this->storeManager->getDefaultStoreView()->getId();
        }
    }

    protected $order; // Magento\Sales\Model\Order

    public function prepareData(Observer $observer)
    {
        if (!$this->shouldAutoClear('orders')) {
            return false;
        }

        $data = $observer->getEvent()->getData();

        if (!isset($data['order'])) {
            return false;
        }

        $this->order = $data['order'];

        return true;
    }

    public function newOrder()
    {
        $items = $this->order->getItems();
        foreach ($items as $item) {
            $tag = $this->tagger->getProductTag((int)$item->getProductId());
            $rawData = [
                'action' => 'invalidation',
                'type' => 'order',
                'tag' => $tag,
                'reasonType' => 'order',
                'storeId' => $this->storeId,
                'reasonEntity' => '#' . $this->order->getId()
            ];
            $this->_publisher->publish(self::TOPIC_NAME, $this->_json->serialize($rawData));
            //$this->invalidateTag($tag, 'order', '#' . $this->order->getId());
        }
    }
}

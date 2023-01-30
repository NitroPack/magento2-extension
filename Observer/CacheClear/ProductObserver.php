<?php

namespace NitroPack\NitroPack\Observer\CacheClear;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Api\TaggingServiceInterface;
use NitroPack\NitroPack\Observer\CacheClearObserver;
use Psr\Log\LoggerInterface;

class ProductObserver extends CacheClearObserver
{


    protected $storeId = 0;

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

    protected static $eventMap = array(
        'catalog_product_save_commit_after' => 'saved',
        'catalog_product_delete_commit_after' => 'deleted'
    );

    protected $product; // Magento\Catalog\Model\Product

    public function prepareData(Observer $observer)
    {
        if (!$this->shouldAutoClear('products')) {
            return false;
        }

        $data = $observer->getEvent()->getData();

        $product = isset($data['product']) ? $data['product'] : $observer->getEvent()->getProduct();
        if (!$product) {
            return false;
        }

        $this->product = $product;

        return true;
    }

    public function saved(Observer $observer)
    {
        $tag = $this->tagger->getProductTag($this->product);
        $productName = $this->product->getName();
        if (!$productName || $productName == '') {
            $productName = '#' . $this->product->getId();
        }

        $rawData = [
            'action' => 'invalidation',
            'type' => 'product',
            'tag' => $tag,
            'reasonType' => 'product',
            'storeId' => $this->storeId,
            'reasonEntity' => $productName
        ];

        $this->_publisher->publish(self::TOPIC_NAME, $this->_json->serialize($rawData));

        //$this->invalidateTag($tag, 'product', $productName);

        foreach ($this->product->getCategoryIds() as $catId) {
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
                $this->_publisher->publish(self::TOPIC_NAME, $this->_json->serialize($rawData));
                //$this->invalidateTag($cTag, 'category for', $productName);
            }
        }
    }

    public function deleted(Observer $observer)
    {
        $tag = $this->tagger->getProductTag($this->product);
        $productName = $this->product->getName();
        if (!$productName || $productName == '') {
            $productName = '#' . $this->product->getId();
        }
        $rawData = [
            'action' => 'purge_tag',
            'type' => 'product',
            'tag' => $tag,
            'reasonType' => 'product',
            'storeId' => $this->storeId,
            'reasonEntity' => $productName
        ];
        $this->_publisher->publish(self::TOPIC_NAME, $this->_json->serialize($rawData));
        //  $this->purgeTagComplete($tag, 'product', $productName);
    }

}

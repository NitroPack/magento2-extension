<?php

namespace NitroPack\NitroPack\Observer\CacheClear;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;

use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Api\TaggingServiceInterface;
use NitroPack\NitroPack\Observer\CacheClearObserver;
use Psr\Log\LoggerInterface;

class CategoryObserver extends CacheClearObserver
{

    protected $storeId = 0;

    protected static $eventMap = array(
        'catalog_category_save_commit_after' => 'saved',
        'catalog_category_delete_commit_after' => 'deleted',
        'catalog_category_change_products' => 'productsChanged',
        'category_move' => 'moved'
    );

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

    protected $category; // Magento\Catalog\Model\Category

    public function prepareData(Observer $observer)
    {
        if (!$this->shouldAutoClear('categories')) {
            return false;
        }

        $data = $observer->getEvent()->getData();

        if (!isset($data['category'])) {
            return false;
        }

        $this->category = $data['category'];

        return true;
    }

    public function saved(Observer $observer)
    {
        $tag = $this->tagger->getCategoryTag($this->category);
        $categoryName = $this->category->getName();
        if (!$categoryName || $categoryName == '') {
            $categoryName = $this->category->getId();
        }
        $rawData = [
            'action' => 'invalidation',
            'type' => 'category',
            'tag' => $tag,
            'reasonType' => 'category',
            'storeId' => $this->storeId,
            'reasonEntity' => $categoryName
        ];
        $this->_publisher->publish($this->defaultQueueValueConnection =='amqp' ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB, $this->_json->serialize($rawData));
        //  $this->invalidateTag($tag, 'category', $categoryName);
    }

    public function deleted(Observer $observer)
    {
        $tag = $this->tagger->getCategoryTag($this->category);
        $categoryName = $this->category->getName();
        if (!$categoryName || $categoryName == '') {
            $categoryName = '#' . $this->category->getId();
        }
        $rawData = [
            'action' => 'purge_tag',
            'type' => 'category',
            'tag' => $tag,
            'reasonType' => 'category',
            'storeId' => $this->storeId,
            'reasonEntity' => $categoryName
        ];
        $this->_publisher->publish($this->defaultQueueValueConnection =='amqp' ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB, $this->_json->serialize($rawData));
        //$this->purgeTagComplete($tag, 'category', $categoryName);
    }

    public function productsChanged(Observer $observer)
    {
        $tag = $this->tagger->getCategoryTag($this->category);
        $categoryName = $this->category->getName();
        if (!$categoryName || $categoryName == '') {
            $categoryName = '#' . $this->category->getId();
        }
        $rawData = [
            'action' => 'invalidation',
            'type' => 'category',
            'tag' => $tag,
            'reasonType' => 'category',
            'storeId' => $this->storeId,
            'reasonEntity' => $categoryName
        ];
        $this->_publisher->publish($this->defaultQueueValueConnection =='amqp' ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB, $this->_json->serialize($rawData));
        //  $this->invalidateTag($tag, 'category', $categoryName);
    }

    public function moved(Observer $observer)
    {
        $tag = $this->tagger->getCategoryTag($this->category);
        $categoryName = $this->category->getName();
        if (!$categoryName || $categoryName == '') {
            $categoryName = '#' . $this->category->getId();
        }
        $rawData = [
            'action' => 'invalidation',
            'type' => 'category',
            'tag' => $tag,
            'reasonType' => 'category',
            'storeId' => $this->storeId,
            'reasonEntity' => $categoryName
        ];
        $this->_publisher->publish($this->defaultQueueValueConnection =='amqp' ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB, $this->_json->serialize($rawData));
        // $this->invalidateTag($tag, 'category', $categoryName);
    }

}

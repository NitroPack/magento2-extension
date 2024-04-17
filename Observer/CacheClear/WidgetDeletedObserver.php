<?php

namespace NitroPack\NitroPack\Observer\CacheClear;

use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;

class WidgetDeletedObserver implements ObserverInterface
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
     * @var TypeListInterface
     * */
    protected $cacheTypeList;
    /**
     * @var Pool
     * */
    protected $cacheFrontendPool;
    /**
     * @param RequestInterface  $request
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\MessageQueue\PublisherInterface   $publisher
     * @param \Magento\Framework\MessageQueue\DefaultValueProvider $defaultQueueValueProvider
     * @param \Magento\Framework\Serialize\Serializer\Json         $json
     * @param DeploymentConfig                                     $config
     * @param TypeListInterface $cacheTypeList
     * @param Pool $cacheFrontendPool
     * */
    public function __construct(
        RequestInterface                                     $request,
        StoreManagerInterface                                $storeManager,
        \Magento\Framework\MessageQueue\PublisherInterface   $publisher,
        \Magento\Framework\MessageQueue\DefaultValueProvider $defaultQueueValueProvider,
        \Magento\Framework\Serialize\Serializer\Json         $json,
        DeploymentConfig                                     $config,
        TypeListInterface $cacheTypeList,
        Pool              $cacheFrontendPool
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
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        if ($this->storeId == 0) {
            $this->storeId = $this->storeManager->getDefaultStoreView()->getId();
        }
    }

    public function execute(Observer $observer)
    {

        $widgetInstance = $observer->getData('data_object');
        $parameter = $widgetInstance->getWidgetParameters();
            if (isset($parameter['block_id'])) {
                $blockId = $parameter['block_id'];
                $blockName = '#' . $blockId;
                $rawData = [
                    'action' => 'invalidation',
                    'type' => 'block',
                    'tag' => 'cms_b_'.$blockId ,
                    'reasonType' => 'block',
                    'storeId' => $this->storeId,
                    'reasonEntity' => $blockName
                ];
                $this->publisher->publish($this->getTopicName(), $this->json->serialize($rawData));
            }
        $types = ['full_page'];
        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }

    public function getTopicName()
    {
        return $this->defaultQueueValueConnection == 'amqp' && $this->config->get('queue/amqp') && count($this->config->get('queue/amqp')) > 0 ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB;
    }

}

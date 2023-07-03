<?php

namespace NitroPack\NitroPack\Observer\CacheClear;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Cms\Model\Page;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;

class PageDeletedObserver implements ObserverInterface
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
    protected $page;
    public function execute(Observer $observer)
    {
        $data = $observer->getEvent()->getData();
        if (!isset($data['object']) || !is_a($data['object'], Page::class)) {
            return false;
        }
        $this->page = $data['object'];
        $pageName = $this->page->getTitle();
        if (!$pageName || $pageName == '') {
            $pageName = '#' . $this->page->getId();
        }
        $rawData = [
            'action' => 'invalidation',
            'type' => 'page',
            'tag' => 'cms_p_'.$this->page->getId(),
            'reasonType' => 'page',
            'storeId' => $this->storeId,
            'reasonEntity' => $pageName
        ];

        $this->publisher->publish($this->getTopicName(), $this->json->serialize($rawData));

    }


    public function getTopicName()
    {
        return $this->defaultQueueValueConnection == 'amqp' && $this->config->get('queue/amqp') && count($this->config->get('queue/amqp')) > 0 ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB;
    }
}

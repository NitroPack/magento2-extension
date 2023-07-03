<?php

namespace NitroPack\NitroPack\Observer\CacheClear;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;

class AttributeSetDeletedObserver implements ObserverInterface
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

        $this->request = $request;
        $this->publisher = $publisher;
        $this->storeManager = $storeManager;
        $this->storeId = $this->request->getParam('store');
        $this->defaultQueueValueProvider = $defaultQueueValueProvider;
        $this->defaultQueueValueProvider->getConnection();
        $this->config = $config;
        $this->json = $json;
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        if ($this->storeId == 0) {
            $this->storeId = $this->storeManager->getDefaultStoreView()->getId();
        }
    }
    protected $set; // Magento\Eav\Model\Entity\Attribute\Set
    public function execute(Observer $observer)
    {
        $data = $observer->getEvent()->getData();

        if (!isset($data['object']) || !is_a($data['object'], Set::class)) {
            return false;
        }

        $this->set = $data['object'];

        $products = $this->findProductsWithAttributeSet();
        $attributeSetName = $this->set->getAttributeSetName();
        if (!$attributeSetName || $attributeSetName == '') {
            $attributeSetName = '#' . $this->set->getAttributeSetId();
        }
        foreach ($products as $product) {
            $rawData = [
                'action' => 'invalidation',
                'type' => 'attribute set',
                'tag' => 'cat_p_'.$product->getId(),
                'reasonType' => 'attribute set',
                'storeId' => $this->storeId,
                'reasonEntity' => $attributeSetName
            ];

            $this->publisher->publish($this->getTopicName() , $this->json->serialize($rawData));

        }
    }

    protected function findProductsWithAttributeSet()
    {
        $productsRepo = $this->objectManager->create(ProductRepositoryInterface::class);
        $searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter(
            'attribute_set_id',
            $this->set->getAttributeSetId(),
            'eq'
        )->create();
        $searchResults = $productsRepo->getList($searchCriteria);
        return $searchResults->getItems();
    }


    public function getTopicName()
    {
        return $this->defaultQueueValueConnection == 'amqp' && $this->config->get('queue/amqp') && count($this->config->get('queue/amqp')) > 0 ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB;
    }

}

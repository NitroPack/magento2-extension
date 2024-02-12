<?php

namespace NitroPack\NitroPack\Observer\CacheClear;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\DeploymentConfig;
use NitroPack\NitroPack\Model\EntityAttributeRelationFactory;
class AttributeDeletedObserver implements ObserverInterface
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
     * @var ProductRepositoryInterface
     * */
    protected $productRepository;
    /**
     * @var SearchCriteriaBuilder
     * */
    protected $searchCriteriaBuilder;
    /**
     * @var EntityAttributeRelationFactory
     * */
    protected $entityAttributeFactory;
    /**
     * @param RequestInterface   $request
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\MessageQueue\PublisherInterface   $publisher
     * @param \Magento\Framework\MessageQueue\DefaultValueProvider $defaultQueueValueProvider
     * @param \Magento\Framework\Serialize\Serializer\Json         $json
     * @param ProductRepositoryInterface                          $productRepository
     * @param SearchCriteriaBuilder                                     $searchCriteriaBuilder
     * @param EntityAttributeRelationFactory                        $entityAttributeFactory
     * @param DeploymentConfig                                     $config
     * */
    public function __construct(
        RequestInterface                                     $request,
        StoreManagerInterface                                $storeManager,
        \Magento\Framework\MessageQueue\PublisherInterface   $publisher,
        \Magento\Framework\MessageQueue\DefaultValueProvider $defaultQueueValueProvider,
        \Magento\Framework\Serialize\Serializer\Json         $json,
        ProductRepositoryInterface                           $productRepository,
        SearchCriteriaBuilder                                $searchCriteriaBuilder,
        EntityAttributeRelationFactory                       $entityAttributeFactory,
        DeploymentConfig                                     $config
    )
    {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->request = $request;
        $this->entityAttributeFactory = $entityAttributeFactory;
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

    protected $attribute; // Magento\Catalog\Model\ResourceModel\Eav\Attribute


    public function execute(Observer $observer)
    {
        $data = $observer->getEvent()->getData();

        if (!isset($data['attribute'])) {
            return false;
        }

        $this->attribute = $data['attribute'];

        $attributeName = $this->attribute->getName();
        if (!$attributeName || $attributeName == '') {
            $attributeName = '#' . $this->attribute->getId();
        }

        $attributeSetIds = $this->findAttributeSetsIncludingAttribute($this->attribute->getId());
        $products = array();
        foreach ($attributeSetIds as $attributeSetId) {
            $productsWithSet = $this->findProductsWithAttributeSet($attributeSetId);
            $products = array_merge($products, $productsWithSet);
        }

        foreach ($products as $product) {

            $rawData = [
                'action' => 'purge_tag',
                'type' => 'widget',
                'tag' => 'cms_b_'.$product->getId(),
                'reasonType' => 'widget',
                'storeId' => $this->storeId,
                'reasonEntity' => $attributeName
            ];
            $this->publisher->publish($this->getTopicName(), $this->json->serialize($rawData));

        }
    }

    protected function findProductsWithAttributeSet($attributeSetId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('attribute_set_id', $attributeSetId, 'eq')->create();
        $searchResults = $this->productRepository->getList($searchCriteria);
        return $searchResults->getItems();
    }

    protected function findAttributeSetsIncludingAttribute($attributeId)
    {
        $model = $this->entityAttributeFactory->create()->getCollection();
        $collection= $model->addFieldToFilter('attribute_id', array('eq' => $attributeId));

        $attributeSetIds = array();
        foreach ($collection as $attr) {
            $attributeSetIds[] = $attr->getData('attribute_set_id');
        }

        return $attributeSetIds;
    }


    public function getTopicName()
    {
        return $this->defaultQueueValueConnection == 'amqp' && $this->config->get('queue/amqp') && count($this->config->get('queue/amqp')) > 0 ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB;
    }
}

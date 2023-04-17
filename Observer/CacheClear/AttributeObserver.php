<?php

namespace NitroPack\NitroPack\Observer\CacheClear;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Api\TaggingServiceInterface;
use NitroPack\NitroPack\Model\EntityAttributeRelationFactory;
use NitroPack\NitroPack\Observer\CacheClearObserver;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\DeploymentConfig;

class AttributeObserver extends CacheClearObserver
{

    protected static $eventMap = array(
        'catalog_entity_attribute_save_commit_after' => 'saved',
        'catalog_entity_attribute_delete_commit_after' => 'deleted'
    );


    protected $storeId = 0;

    public function __construct(
        NitroServiceInterface                                $nitro,
        TaggingServiceInterface                              $tagger,
        RequestInterface                                     $request,
        StoreManagerInterface                                $storeManager,
        \Magento\Framework\MessageQueue\DefaultValueProvider $defaultQueueValueProvider,
        \Magento\Framework\MessageQueue\PublisherInterface   $publisher,
        \Magento\Framework\Serialize\Serializer\Json         $json,
        LoggerInterface                                      $logger,
        DeploymentConfig $config
    )
    {
        parent::__construct($nitro, $tagger, $request, $storeManager, $logger, $defaultQueueValueProvider, $publisher, $json,$config);
        $this->storeId = $this->request->getParam('store');
        if ($this->storeId == 0) {
            $this->storeId = $this->storeManager->getDefaultStoreView()->getId();
        }

    }

    protected $attribute; // Magento\Catalog\Model\ResourceModel\Eav\Attribute

    public function prepareData(Observer $observer)
    {
        if (!$this->shouldAutoClear('attributes')) {
            return false;
        }

        $data = $observer->getEvent()->getData();

        if (!isset($data['attribute'])) {
            return false;
        }

        $this->attribute = $data['attribute'];

        return true;
    }

    public function saved(Observer $observer)
    {
        $attributeName = $this->attribute->getName();
        if (!$attributeName || $attributeName == '') {
            $attributeName = '#' . $this->attribute->getId();
        }

        $attributeSetIds = $this->findAttributeSetsIncludingAttribute($this->attribute->getId());

        $products = array();
        foreach ($attributeSetsIds as $attributeSetId) {
            $productsWithSet = $this->findProductsWithAttributeSet($attributeSetId);
            $products = array_merge($products, $productsWithSet);
        }

        foreach ($products as $product) {
            $tag = $this->tagger->getProductTag($product);
            $rawData = [
                'action' => 'invalidation',
                'type' => 'attribute',
                'tag' => $tag,
                'reasonType' => 'attribute',
                'storeId' => $this->storeId,
                'reasonEntity' => $attributeName
            ];

            $this->_publisher->publish($this->getTopicName(), $this->_json->serialize($rawData));
            //$this->invalidateTag($tag, 'attribute', $attributeName);
        }
    }

    public function deleted(Observer $observer)
    {
        $attributeName = $this->attribute->getName();
        if (!$attributeName || $attributeName == '') {
            $attributeName = '#' . $this->attribute->getId();
        }

        $attributeSetIds = $this->findAttributeSetsIncludingAttribute($this->attribute->getId());

        $products = array();
        foreach ($attributeSetsIds as $attributeSetId) {
            $productsWithSet = $this->findProductsWithAttributeSet($attributeSetId);
            $products = array_merge($products, $productsWithSet);
        }

        foreach ($products as $product) {
            $tag = $this->tagger->getProductTag($product);
            $rawData = [
                'action' => 'purge_tag',
                'type' => 'widget',
                'tag' => 'block',
                'reasonType' => 'widget',
                'storeId' => $this->storeId,
                'reasonEntity' => $attributeName
            ];
            $this->_publisher->publish($this->getTopicName(), $this->_json->serialize($rawData));
            //$this->purgeTagPageCache($tag, 'attribute', $attributeName);
        }
    }

    protected function findProductsWithAttributeSet($attributeSetId)
    {
        $productsRepo = $this->objectManager->create(ProductRepositoryInterface::class);
        $searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('attribute_set_id', $attributeSetId, 'eq')->create();
        $searchResults = $productsRepo->getList($searchCriteria);
        return $searchResults->getItems();
    }

    protected function findAttributeSetsIncludingAttribute($attributeId)
    {
        $entityAttributeFactory = $this->objectManager->create(EntityAttributeRelationFactory::class);
        $model = $entityAttributeFactory->create();
        $collection = $model->getCollection();
        $collection->addFieldToFilter('attribute_id', array('eq' => $attributeId));

        $attributeSetIds = array();
        foreach ($collection as $attr) {
            $attributeSetIds[] = $attr->getData('attribute_set_id');
        }

        return $attributeSetIds;
    }
}

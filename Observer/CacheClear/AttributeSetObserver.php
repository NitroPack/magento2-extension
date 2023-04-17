<?php

namespace NitroPack\NitroPack\Observer\CacheClear;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Api\TaggingServiceInterface;
use NitroPack\NitroPack\Observer\CacheClearObserver;
use Psr\Log\LoggerInterface;

class AttributeSetObserver extends CacheClearObserver
{


    protected $storeId = 0;

    protected static $eventMap = array(
        'eav_entity_attribute_set_save_commit_after' => 'saved',
        'eav_entity_attribute_set_delete_commit_after' => 'deleted'
    );

    public function __construct(
        NitroServiceInterface $nitro,
        TaggingServiceInterface $tagger,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        \Magento\Framework\MessageQueue\DefaultValueProvider $defaultQueueValueProvider,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\Framework\Serialize\Serializer\Json $json,
        LoggerInterface $logger,
        DeploymentConfig $config
    ) {

        parent::__construct($nitro, $tagger, $request, $storeManager, $logger,$defaultQueueValueProvider, $publisher, $json,$config);
        $this->storeId = $this->request->getParam('store');
        if ($this->storeId == 0) {
            $this->storeId = $this->storeManager->getDefaultStoreView()->getId();
        }
    }

    protected $set; // Magento\Eav\Model\Entity\Attribute\Set

    public function prepareData(Observer $observer)
    {
        if (!$this->shouldAutoClear('attributeSets')) {
            return false;
        }

        $data = $observer->getEvent()->getData();

        if (!isset($data['object']) || !is_a($data['object'], Set::class)) {
            return false;
        }

        $this->set = $data['object'];

        return true;
    }

    public function saved(Observer $observer)
    {
        $products = $this->findProductsWithAttributeSet();
        $attributeSetName = $this->set->getAttributeSetName();
        if (!$attributeSetName || $attributeSetName == '') {
            $attributeSetName = '#' . $this->set->getAttributeSetId();
        }
        foreach ($products as $product) {
            $tag = $this->tagger->getProductTag($this->product);
            $rawData = [
                'action' => 'invalidation',
                'type' => 'attribute set',
                'tag' => $tag,
                'reasonType' => 'attribute set',
                'storeId' => $this->storeId,
                'reasonEntity' => $attributeSetName
            ];

            $this->_publisher->publish($this->getTopicName() , $this->_json->serialize($rawData));
            //$this->invalidateTag($tag, 'attribute set', $attributeSetName);
        }
    }

    public function deleted(Observer $observer)
    {
        $products = $this->findProductsWithAttributeSet();
        $attributeSetName = $this->set->getAttributeSetName();
        if (!$attributeSetName || $attributeSetName == '') {
            $attributeSetName = '#' . $this->set->getAttributeSetId();
        }
        foreach ($products as $product) {
            $tag = $this->tagger->getProductTag($product);
            $rawData = [
                'action' => 'invalidation',
                'type' => 'attribute set',
                'tag' => $tag,
                'reasonType' => 'attribute set',
                'storeId' => $this->storeId,
                'reasonEntity' => $attributeSetName
            ];

            $this->_publisher->publish($this->getTopicName() , $this->_json->serialize($rawData));
            //$this->invalidateTag($tag, 'attribute set', $attributeSetName);
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

}

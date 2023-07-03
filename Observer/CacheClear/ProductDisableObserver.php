<?php

namespace NitroPack\NitroPack\Observer\CacheClear;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Indexer\Model\IndexerFactory;


class ProductDisableObserver implements ObserverInterface
{
    /**
     * @var IndexerFactory
     * */
    protected  $indexerFactory;
    /**
     * @var \Magento\Framework\App\Cache\Frontend\Pool
     * */
    protected $_cacheFrontendPool;
    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     * */
    protected $_cacheTypeList;
    public function __construct(
        IndexerFactory $indexerFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
    ){
        $this->indexerFactory = $indexerFactory;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;
    }
    public function execute(Observer $observer)
    {
        $product = $observer->getProduct(); // Assuming you want to observe product save

        // Retrieve the original data from the product object
        $originalData = $product->getOrigData();
        // Retrieve the new data from the product object
        $newData = $product->getData();
        // Perform actions based on the changes
        if ($newData['status'] !=$originalData['status']) {
            $indexer = $this->indexerFactory->create();
            $indexer->load('catalogsearch_fulltext');
            if ($indexer->getStatus() != 'valid' || $indexer->isScheduled()) {
                $indexer->reindexAll();
                $this->_cacheTypeList->cleanType('collections');
                foreach ($this->_cacheFrontendPool as $cacheFrontend) {
                    $cacheFrontend->getBackend()->clean();
                }
            }
            // Changes detected, perform your logic here
        }
    }
}

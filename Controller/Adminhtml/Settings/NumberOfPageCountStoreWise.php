<?php

namespace NitroPack\NitroPack\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;

class NumberOfPageCountStoreWise  extends StoreAwareAction
{


    /**
     * @var \Magento\Sitemap\Model\ResourceModel\Catalog\CategoryFactory
     * */
    protected $_categoryFactory;
    /**
     * @var \Magento\Sitemap\Model\ResourceModel\Catalog\ProductFactory
     * */
    protected $_productFactory;
    /**
     * @var \Magento\Sitemap\Model\ResourceModel\Cms\PageFactory
     * */
    protected $_pageFactory;
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;

    /**
     * @var JsonFactory
     * */
    protected $resultJsonFactory;

    public function __construct(
        Context                                                $context,
        NitroServiceInterface $nitro,
        JsonFactory                                            $resultJsonFactory,
        ScopeConfigInterface                                   $_scopeConfig,
        \Magento\Sitemap\Model\ResourceModel\Catalog\ProductFactory $productFactory,
        \Magento\Sitemap\Model\ResourceModel\Catalog\CategoryFactory $categoryFactory,
        \Magento\Sitemap\Model\ResourceModel\Cms\PageFactory $pageFactory
    ){
        parent::__construct($context, $nitro);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_scopeConfig = $_scopeConfig;
        $this->_categoryFactory = $categoryFactory;
        $this->_productFactory = $productFactory;
        $this->_pageFactory = $pageFactory;
    }
    protected function nitroExecute()
    {
        try {
            return $this->resultJsonFactory->create()->setData(array(
                'number_of_pages' => $this->getPageCount($this->storeGroup),

            ));
        } catch (\Exception $e) {
            return $this->resultJsonFactory->create()->setData(array(
                'number_of_pages' => false,

            ));
        }
    }


    public function getPageCount($storesData)
    {

        $pageCollection = $this->_pageFactory->create()->getCollection($storesData->getDefaultStoreId());
        $productCollection = $this->_productFactory->create()->getCollection($storesData->getDefaultStoreId());
        $categoryCollection = $this->_categoryFactory->create()->getCollection($storesData->getDefaultStoreId());
        return (int)count($pageCollection) + (int)count($productCollection) + (int)count($categoryCollection);
    }

}

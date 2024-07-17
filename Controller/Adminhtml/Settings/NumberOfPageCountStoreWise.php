<?php

/**
 * NitroPack
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the nitropack.io license that is
 * available through the world-wide-web at this URL:
 * https://github.com/NitroPack/magento2-extension/blob/716247d40d2de7b84f222c6a93761d87b6fe5b7b/LICENSE
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Site Optimization
 * @subcategory Performance
 * @package     NitroPack_NitroPack
 * @author      NitroPack Inc.
 * @copyright   Copyright (c) NitroPack (https://www.nitropack.io/)
 * @license     https://github.com/NitroPack/magento2-extension/blob/716247d40d2de7b84f222c6a93761d87b6fe5b7b/LICENSE
 */
namespace NitroPack\NitroPack\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;

/**
 * Class NumberOfPageCountStoreWise - Controller to get the number of pages in the store
 * @extends StoreAwareAction
 * @package NitroPack\NitroPack\Controller\Adminhtml\Settings
 * @since 3.0.0
 */
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

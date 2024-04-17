<?php

namespace NitroPack\NitroPack\Block\Dashboard;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;

class ProductAttributeBlock extends Template
{
    /**
     * @var UrlInterface
     * */
    protected $_backendUrl;
    /**
     * @var  CollectionFactory
     * */
    protected $attributeCollectionFactory;

    public function __construct(
        Context      $context, // required as part of the Magento\Backend\Block\Template constructor
        UrlInterface $_backendUrl,
        array        $data = [] // required as part of the Magento\Backend\Block\Template constructor
    )
    {
        $this->_backendUrl = $_backendUrl;
        parent::__construct($context, $data);
    }


    public function getProductAttributeUrl()
    {
        // route the magento System -> Configuration -> Advanced -> Full Page Cache
        return $this->getBackendUrl('catalog/product_attribute/index', false, false);
    }


    protected function getBackendUrl($route, $withStore = true, $withFormKey = false)
    {
        $params = array();
        if ($withStore) {
            $params['group'] = $this->getStoreGroup()->getId();
        }
        if ($withFormKey) {
            $params['form_key'] = $this->getFormKey();
        }
        return $this->_backendUrl->getUrl($route, $params);
    }

}

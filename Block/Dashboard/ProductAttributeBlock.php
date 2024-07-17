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

namespace NitroPack\NitroPack\Block\Dashboard;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
/**
 * Class ProductAttributeBlock - Block for the NitroPack Each Widget admin dashboard
 * @block
 * @extends Template
 * @package NitroPack\NitroPack\Block\Dashboard
 * @since 3.0.0
 */
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

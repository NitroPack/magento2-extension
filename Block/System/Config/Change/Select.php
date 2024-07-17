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

namespace NitroPack\NitroPack\Block\System\Config\Change;


use Magento\Backend\Block\Template\Context;
use NitroPack\NitroPack\Helper\FastlyHelper;
/**
 * Class Select - Admin Block Config Popup On Cache Application Change
 * @block
 * @extends \Magento\Config\Block\System\Config\Form\Field
 * @package NitroPack\NitroPack\Block\Dashboard
 * @since 3.0.0
 */
class Select extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \NitroPack\NitroPack\Helper\FastlyHelper
     * */
    protected $fastlyHelper;

    public function __construct(FastlyHelper $fastlyHelper,Context $context,array $data = [])
    {
        parent::__construct($context, $data);
        $this->fastlyHelper = $fastlyHelper;
    }

    /**
     * Retrieve element HTML markup
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setOnchange('checkNitroPack(this)');
        $html = $element->getElementHtml();

        $checkFastlyModule = $this->fastlyHelper->isFastlyModuleEnabled();

        $html .= '<script type="text/javascript">function checkNitroPack(self){ if( (self.value==3 && ' . $checkFastlyModule . ') || (self.value!=3 && self.value!=42) ){  require(["Magento_Ui/js/modal/alert","jquery"], function(alert, $) { ' .
            ' alert({modalClass: "popup",title: self.value==3 && ' . $checkFastlyModule . ' ? $.mage.__("Fastly CDN Detected"):$.mage.__("NitroPack requies to be selected as a Full Page Cache Application"),' .
            'content: self.value==3 && ' . $checkFastlyModule . ' ? $.mage.__("</br>To use both Fastly CDN and NitroPack, you need to select \"Fastly CDN\" as your caching app, and select \"Yes\" to enable NitroPack.</br></br>If you proceed by setting \"NitroPack\" as your full page caching of choice, it would not work along with Fastly CDN.</br></br>Confirm your choice to proceed and use \"NitroPack\" without Fastly CDN.") :  $.mage.__("<br>Choosing an other option will disable the NitroPack. Are you sure want to proceed ?"),' .
            'buttons: [{ text: $.mage.__("Ok"), class: "action-primary action-accept" ,click: function () { this.closeModal(true); } },{ text: $.mage.__("Cancel"), class:"action-secondary action-new",click: function () {  self.value = self.value ==3 && '.$checkFastlyModule.' ? 42 : 3 ; self.dispatchEvent(new Event("change"));  this.closeModal(true); }  }] }) })' .
            '   } }</script>';
        return $html;
    }
}



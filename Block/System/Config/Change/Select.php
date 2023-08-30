<?php

namespace NitroPack\NitroPack\Block\System\Config\Change;

class Select extends \Magento\Config\Block\System\Config\Form\Field
{
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
        $html .= '<script type="text/javascript">function checkNitroPack(self){ if(self.value!=3){  require(["Magento_Ui/js/modal/alert","jquery"], function(alert, $) { '.
            ' alert({modalClass: "popup",title: $.mage.__("NitroPack requies to be selected as a Full Page Cache Application"),'.
            'content: $.mage.__("<br>Choosing an other option will disable the NitroPack. Are you sure want to proceed ?"),'.
            'buttons: [{ text: $.mage.__("Ok"), class: "action-primary action-accept" ,click: function () { this.closeModal(true); } },{ text: $.mage.__("Cancel"), class:"action-secondary action-new",click: function () { self.value = 3; self.dispatchEvent(new Event("change"));  this.closeModal(true); }  }] }) })'.
            '   } }</script>';
        return $html;
    }
}



<?php

namespace NitroPack\NitroPack\Plugin\Block;

use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;

class MassactionConfirmationBox
{
    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;
    /**
     * @var \NitroPack\NitroPack\Helper\ApiHelper
     * */
    protected $apiHelper;

    public function __construct(NitroServiceInterface $nitro, \NitroPack\NitroPack\Helper\ApiHelper $apiHelper)
    {
        $this->nitro = $nitro;
        $this->apiHelper = $apiHelper;
    }

    /**
     * Retrieve apply button html
     *
     * @return string
     */
    public function afterGetApplyButtonHtml(\Magento\Backend\Block\Widget\Grid\Massaction $subject)
    {
        return $subject->getButtonHtml(__('Submit'), $this->submitActionJavaScript($subject));
    }

    /**
     * Get submit action javascript code.
     *
     * @return string
     */
    public function submitActionJavaScript($subject)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $storeRepo = $objectManager->create(\Magento\Store\Api\GroupRepositoryInterface::class);
        $storeGroup = $storeRepo->getList();
        $haveConnected =false;
        foreach ($storeGroup as $storesData) {
            $settingsFilename = $this->apiHelper->getSettingsFilename($storesData->getCode());
            $setting = json_decode($this->apiHelper->readFile($settingsFilename));
            if (!is_null($setting) && $setting->enabled) {
                $haveConnected = true;
            }
        }

        if($haveConnected){
            return "require(['Magento_Ui/js/modal/alert','jquery'], function(alert, $) { " . "\n" .
                "var checkedSystemCache = []; $('input[name=\'types\']:checked').each(function() { checkedSystemCache.push($(this).val())});" . "\n" .
                " if($('#cache_grid_massaction-select').val()=='disable' && $('input[name=\'types\']').is(':checked') && $.inArray('full_page',checkedSystemCache) !== -1 ){" . "\n" .
                "alert({modalClass: 'popup',title: $.mage.__('NitroPack requies Full Page Caching to be enabled'),content: " . "\n" .
                "$.mage.__('<br>Choosing an other option will disable the NitroPack. Are you sure want to proceed ?')," . "\n" .
                " buttons: [{ text: $.mage.__('Ok'), class: 'action-primary action-accept'" . "\n" .
                ",click: function () {" . $subject->getJsObjectName(
                ) . ".apply() }},{ text: $.mage.__('Cancel'),class: 'action-secondary action-new',click: function () { this.closeModal(true); }    }] }); }" . "\n" .
                " else{ " . $subject->getJsObjectName() . ".apply(); } });";
        } else {
            return $subject->getJsObjectName() . ".apply();";
        }
    }
}


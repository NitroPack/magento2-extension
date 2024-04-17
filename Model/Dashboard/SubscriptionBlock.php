<?php

namespace NitroPack\NitroPack\Model\Dashboard;

class SubscriptionBlock implements \Magento\Framework\View\Element\Block\ArgumentInterface
{

    protected $nitro = null;

    public function getPlanDetailUrl()
    {
        return $this->nitro->integrationUrl('plan_details_json');
    }


    public function setNitro($nitro)
    {
        return $this->nitro = $nitro;
    }


    public function getNitro()
    {
        return $this->nitro;
    }


}

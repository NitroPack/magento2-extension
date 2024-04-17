<?php

namespace NitroPack\NitroPack\Model\Dashboard;

class OptimizationPage implements \Magento\Framework\View\Element\Block\ArgumentInterface
{

    protected $nitro = null;

    public function getOptimizePageUrl()
    {
        return $this->nitro->integrationUrl('optimization_details_json');
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

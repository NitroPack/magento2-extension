<?php

namespace NitroPack\NitroPack\Model\Dashboard;

class OptimizationMode implements \Magento\Framework\View\Element\Block\ArgumentInterface
{

    protected $nitro = null;

    public function getOptimizeModeUrl()
    {
        return $this->nitro->integrationUrl('quicksetup_json');
    }


    public function getSaveOptimizeMode()
    {
        return $this->nitro->integrationUrl('quicksetup');
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

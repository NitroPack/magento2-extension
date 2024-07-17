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
namespace NitroPack\NitroPack\Model\Dashboard;

/**
 * Class OptimizationMode - Optimization Mode ViewModel
 * @extends \Magento\Framework\View\Element\Block\ArgumentInterface
 * @package NitroPack\NitroPack\Model\Dashboard
 * @since 3.0.0
 * */
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

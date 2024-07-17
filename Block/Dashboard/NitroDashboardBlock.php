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
use Magento\Framework\View\Asset\Repository;
/**
 * Class NitroDashboardBlock - Block for the NitroPack Widget i.e OptimizedPages,OptimizationMode,SubscriptionBlock  admin dashboard
 * @block
 * @extends Template
 * @package NitroPack\NitroPack\Block\Dashboard
 * @since 3.0.0
 */
class NitroDashboardBlock extends Template
{

    /**
     * @var Repository
     * */
    protected $assetRepository;


    public function __construct(
        Context    $context, // required as part of the Magento\Backend\Block\Template constructor
        Repository $assetRepository,
        array      $data = [] // required as part of the Magento\Backend\Block\Template constructor
    )
    {
        $this->assetRepository = $assetRepository;
        parent::__construct($context, $data);
    }

    public function getImage($imageName)
    {
        return $this->assetRepository->getUrl("NitroPack_NitroPack::img/$imageName");
    }
}

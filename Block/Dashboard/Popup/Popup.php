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

namespace NitroPack\NitroPack\Block\Dashboard\Popup;

use Magento\Backend\Block\Template;
use Magento\Framework\View\Asset\Repository;

/**
 * Class Popup - Block for the NitroPack popup admin dashboard
 * @block
 * @extends Template
 * @package NitroPack\NitroPack\Block\Dashboard\Popup
 * @since 3.0.0
 */
class Popup extends Template
{
    /**
     * @var Repository
     */
    protected $assetRepository;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Repository                              $assetRepository,
        array                                   $data = []
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

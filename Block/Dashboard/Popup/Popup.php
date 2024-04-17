<?php

namespace NitroPack\NitroPack\Block\Dashboard\Popup;

use Magento\Backend\Block\Template;
use Magento\Framework\View\Asset\Repository;

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

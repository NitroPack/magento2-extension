<?php

namespace NitroPack\NitroPack\Block\Dashboard;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Asset\Repository;

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

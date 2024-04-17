<?php

namespace NitroPack\NitroPack\Block\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Block\SettingsBlock;

class GeneralBlock extends SettingsBlock
{
    /**
     * @var Repository
     */
    protected $assetRepository;

    public function __construct(Context               $context, // required as part of the Magento\Backend\Block\Template constructor
                                NitroServiceInterface $nitro, // dependency injection'ed
                                UrlInterface          $backendUrl, // dependency injection'ed
                                StoreManagerInterface $storeManager, // dependency injection'ed
                                RequestInterface      $request, // dependency injection'ed
                                ScopeConfigInterface  $scopeConfig, // dependency injection'ed
                                TypeListInterface     $cacheTypeList, // dependency injection'ed
                                Store                 $store, // dependency injection'ed
                                Repository            $assetRepository,
                                array                 $data = [] // required as part of the Magento\Backend\Block\Template constructor
    )
    {
        $this->assetRepository = $assetRepository;
        parent::__construct($context, $nitro, $backendUrl, $storeManager, $request, $scopeConfig, $cacheTypeList, $store, $data);
    }


    public function getImage($imageName)
    {
        return $this->assetRepository->getUrl("NitroPack_NitroPack::img/$imageName");
    }
}

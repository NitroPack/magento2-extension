<?php

namespace NitroPack\NitroPack\Observer\Source;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use NitroPack\NitroPack\Helper\ApiHelper;

class SourceAddObserver implements ObserverInterface
{
    /**
     * @var \Magento\Storesudo\Api\GroupRepositoryInterface
     * */
    protected $storeGroupRepo;

    /**
     * @var ApiHelper
     * */
    protected $apiHelper;

    /**
     * @param \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo
     * @param ApiHelper $apiHelper
     */
    public function __construct(
        \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo,
        ApiHelper                                   $apiHelper
    )
    {

        $this->apiHelper = $apiHelper;

        $this->storeGroupRepo = $storeGroupRepo;

    }

    public function execute(Observer $observer)
    {


        $storeGroup = $this->storeGroupRepo->getList();
        // Your logic to handle the source addition here
        foreach ($storeGroup as $storesData) {
            $settingsFilename = $this->apiHelper->getSettingsFilename($storesData->getCode());
            $haveData = $this->apiHelper->readFile($settingsFilename);
            if ($haveData) {
                $settings = json_decode($haveData);
                try {
                    $settings->default_stock = false;
                    if ($this->apiHelper->checkDefaultStockAvailable()) {
                        $settings->default_stock = true;
                    }
                    $this->apiHelper->writeFile($settingsFilename, $settings);
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }
    }
}

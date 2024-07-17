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
namespace NitroPack\NitroPack\Observer\Source;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use NitroPack\NitroPack\Helper\ApiHelper;
/**
 * Class SourceAddObserver - Source Add Observer
 * @implements ObserverInterface
 * @package NitroPack\NitroPack\Observer\Source
 * @since 3.1.0
 * */
class SourceAddObserver implements ObserverInterface
{
    /**
     * @var \Magento\Store\Api\GroupRepositoryInterface
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

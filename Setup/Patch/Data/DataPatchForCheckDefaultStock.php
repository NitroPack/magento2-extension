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
namespace NitroPack\NitroPack\Setup\Patch\Data;

use Magento\Framework\Filesystem\DirectoryList;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use NitroPack\NitroPack\Helper\ApiHelper;

class DataPatchForCheckDefaultStock implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var \Magento\Storesudo\Api\GroupRepositoryInterface
     * */
    protected $storeGroupRepo;
    /**
     * @var DirectoryList
     * */
    protected $directoryList;
    /**
     * @var ApiHelper
     * */
    protected $apiHelper;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo
     * @param DirectoryList $directoryList
     * @param ApiHelper $apiHelper
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface                    $moduleDataSetup,
        \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo,
        DirectoryList                               $directoryList,
        ApiHelper                                   $apiHelper

    )
    {

        $this->apiHelper = $apiHelper;
        $this->directoryList = $directoryList;
        $this->storeGroupRepo = $storeGroupRepo;
        /**
         * If before, we pass $setup as argument in install/upgrade function, from now we start
         * inject it with DI. If you want to use setup, you can inject it, with the same way as here
         */
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $storeGroup = $this->storeGroupRepo->getList();

        foreach ($storeGroup as $storesData) {
            $settingsFilename = $this->apiHelper->getSettingsFilename($storesData->getCode());
            $haveData = $this->apiHelper->readFile($settingsFilename);
            if ($haveData) {
                $settings = json_decode($haveData);
                try {
                    $settings->default_stock = false;
                    if($this->apiHelper->checkDefaultStockAvailable()){
                        $settings->default_stock = true;
                    }
                    $this->apiHelper->writeFile($settingsFilename, $settings);
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
                $this->moduleDataSetup->getConnection()->endSetup();
            }
        }
    }


    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

}

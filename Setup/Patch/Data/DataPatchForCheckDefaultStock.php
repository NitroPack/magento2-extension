<?php

namespace NitroPack\NitroPack\Setup\Patch\Data;

use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;
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
     * @var ObjectManagerInterface
     * */
    protected $objectManager;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo
     * @param DirectoryList $directoryList
     * @param ApiHelper $apiHelper
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ModuleDataSetupInterface                    $moduleDataSetup,
        \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo,
        DirectoryList                               $directoryList,
        ApiHelper                                   $apiHelper,
        ObjectManagerInterface                      $objectManager
    )
    {

        $this->apiHelper = $apiHelper;
        $this->objectManager = $objectManager;
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

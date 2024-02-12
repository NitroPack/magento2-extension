<?php

namespace NitroPack\NitroPack\Setup\Patch\Data;

use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use NitroPack\NitroPack\Helper\ApiHelper;

class DataPatchForWelcomeEmail  implements DataPatchInterface
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
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;


    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo
     * @param DirectoryList $directoryList
     * @param ApiHelper $apiHelper
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        ModuleDataSetupInterface                    $moduleDataSetup,
        \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo,
        DirectoryList                               $directoryList,
        ApiHelper                                   $apiHelper,
        \Magento\Framework\Event\ManagerInterface $eventManager

    )
    {
        $this->eventManager = $eventManager;
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
        $haveConnectionFlag = false;

        foreach ($storeGroup as $storesData) {
            $settingsFilename = $this->apiHelper->getSettingsFilename($storesData->getCode());
            $haveData = $this->apiHelper->readFile($settingsFilename);
            if ($haveData) {
                $haveConnectionFlag = true;
            }
        }
        if(!$haveConnectionFlag){
            $this->eventManager->dispatch('install_trigger_email');
        }

        $this->moduleDataSetup->getConnection()->endSetup();
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

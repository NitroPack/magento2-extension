<?php

namespace NitroPack\NitroPack\Setup\Patch\Data;

use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\ApiHelper;
use NitroPack\NitroPack\Helper\NitroPackConfigHelper;
use NitroPack\SDK\HealthStatus;
use NitroPack\SDK\NitroPack;


class DataPatchCustomerLogin implements DataPatchInterface
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
        $storeViewCode = [];
        $error = "";

        foreach ($storeGroup as $storesData) {
            $settingsFilename = $this->apiHelper->getSettingsFilename($storesData->getCode());
            $haveData = $this->apiHelper->readFile($settingsFilename);
            if ($haveData) {
                $settings = json_decode($haveData);
                try {
                    $settings->cache_to_login_customer = true;
                    $settings->x_magento_vary = $this->xMagentoVaryAdd($storesData, $settings);
                    $this->apiHelper->writeFile($settingsFilename, $settings);
                } catch (\Magento\Framework\Exception\FileSystemException $e) {
                    // fallback to using the module directory
                    $error = $e->getMessage();
                }

            }
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


    private function xMagentoVaryAdd($storeGroup, $settings)
    {

        try {
            $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
            $cachePath = $rootPath . 'nitro_cache' . DIRECTORY_SEPARATOR . $settings->siteId;
            list($allCustomerXMagentoVary, $allXMagentoVary) = $this->apiHelper->getAllCustomerGroupDataForPossibleXMagentoVary($storeGroup);
            $x_magento_vary = $allCustomerXMagentoVary;
            $sdk = new NitroPack(
                $settings->siteId, $settings->siteSecret, null, null, $cachePath
            );
            $sdk->getApi()->setVariationCookie('X-Magento-Vary', $allXMagentoVary, 1);
            $sdk->getApi()->unsetVariationCookie('store');
            return $x_magento_vary;
        } catch (\Exception $e) {
            return null;
        }

    }
}

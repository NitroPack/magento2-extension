<?php

namespace NitroPack\NitroPack\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Module\StatusFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use NitroPack\NitroPack\Model\FullPageCache\PurgeInterface;



class Uninstall implements UninstallInterface
{
    /**
     * @var \NitroPack\NitroPack\Model\NitroPackEvent\Trigger
     * */
    protected $trigger;
    /**
     * @var WriterInterface
     * */
    protected $configWriter;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     * */
    protected $scopeConfig;
    /**
     * @var PurgeInterface
     * */
    protected $purgeInterface;
    /**
     * @var  \Magento\Framework\Filesystem\Driver\File
     * */
    protected $fileDriver;
    /**
     * @var \NitroPack\NitroPack\Helper\ApiHelper
     * */
    protected $apiHelper;
    /**
     * @var \Magento\Store\Api\GroupRepositoryInterface
     * */
    protected $storeGroupRepo;

    public function __construct(
        \NitroPack\NitroPack\Model\NitroPackEvent\Trigger $trigger,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        WriterInterface $configWriter,
        PurgeInterface $purgeInterface,
        \NitroPack\NitroPack\Helper\ApiHelper $apiHelper,
        \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->trigger = $trigger;
        $this->fileDriver = $fileDriver;
        $this->apiHelper = $apiHelper;
        $this->configWriter = $configWriter;
        $this->purgeInterface = $purgeInterface;
        $this->scopeConfig = $scopeConfig;
        $this->storeGroupRepo = $storeGroupRepo;
    }

    public function uninstall(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();
        //TRIGGER AN EVENT OF DISCONNECTION
        $this->trigger->hitEvent('disconnect', false);
        $storeGroup = $this->storeGroupRepo->getList();
        foreach ($storeGroup as $storesData) {
            $this->disconnection($storesData->getCode());
        }
        //TRIGGER AN EVENT OF UNINSTALL
        $this->trigger->hitEvent('uninstall', false);
        if ($this->scopeConfig->getValue('system/full_page_cache/varnish_enable')) {
            $this->setData('system/full_page_cache/caching_application', \Magento\PageCache\Model\Config::VARNISH);
        } else {
            $this->setData('system/full_page_cache/caching_application', \Magento\PageCache\Model\Config::BUILT_IN);
        }

        $this->purgeInterface->purge();
        $setup->endSetup();
    }


    public function setData($path, $value)
    {
        $this->configWriter->save($path, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
    }

    public static function composerUninstall()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $trigger = $objectManager->create(\NitroPack\NitroPack\Model\NitroPackEvent\Trigger::class);
        $scopeConfig = $objectManager->create(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $configWriter = $objectManager->create(WriterInterface::class);
        $trigger->hitEvent('uninstall', false);

        if ($scopeConfig->getValue('system/full_page_cache/varnish_enable')) {
            $configWriter->save(
                'system/full_page_cache/caching_application',
                \Magento\PageCache\Model\Config::VARNISH,
                $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $scopeId = 0
            );
        } else {
            $configWriter->save(
                'system/full_page_cache/caching_application',
                \Magento\PageCache\Model\Config::BUILT_IN,
                $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $scopeId = 0
            );
        }
        $purgeInterface = $objectManager->create(\NitroPack\NitroPack\Model\FullPageCache\PurgeInterface::class);
        $purgeInterface->purge();
    }

    function disconnection($storeGroupCode)
    {
        $settingsFilename = $this->apiHelper->getSettingsFilename($storeGroupCode);
        if ($this->fileDriver->isExists($settingsFilename) && $this->fileDriver->isWritable($settingsFilename)) {
            unlink($settingsFilename);
        }
    }
}

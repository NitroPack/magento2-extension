<?php

namespace NitroPack\NitroPack\Setup\Patch\Data;

use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use NitroPack\NitroPack\Helper\AdminFrontendUrl;
use NitroPack\NitroPack\Helper\ApiHelper;
use NitroPack\SDK\NitroPack;
use NitroPack\Url\Url as NitropackUrl;

class DataPatchForWebhook implements DataPatchInterface
{
    /**
     * @var AdminFrontendUrl
     * */
    protected $urlHelper;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var \Magento\Store\Api\GroupRepositoryInterface
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
     * @var  \Magento\Framework\Encryption\EncryptorInterface
     * */
    protected $encryptor;
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo
     * @param DirectoryList $directoryList
     * @param ApiHelper $apiHelper
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param AdminFrontendUrl $urlHelper
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        ModuleDataSetupInterface                    $moduleDataSetup,
        \Magento\Store\Api\GroupRepositoryInterface $storeGroupRepo,
        DirectoryList                               $directoryList,
        ApiHelper                                   $apiHelper,
        AdminFrontendUrl                            $urlHelper,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor


    )
    {
        $this->urlHelper = $urlHelper;
        $this->apiHelper = $apiHelper;
        $this->directoryList = $directoryList;
        $this->storeGroupRepo = $storeGroupRepo;
        $this->encryptor = $encryptor;
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
                $rootPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
                try {
                    $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
                } catch (\Magento\Framework\Exception\FileSystemException $e) {
                    // fallback to using the module directory
                }
                $cachePath = $rootPath . 'nitro_cache' . DIRECTORY_SEPARATOR . $settings->siteId;
                try {
                    $sdk = new NitroPack(
                        $settings->siteId, $settings->siteSecret, null, null, $cachePath
                    );
                    $token = $this->nitroGenerateWebhookToken($settings->siteId);
                    $urls = $this->getWebhookUrls($token);
                    foreach ($urls as $type => $url) {
                        $sdk->getApi()->setWebhook($type, $url);
                    }
                } catch (\Exception $exception) {
                    $error = $exception->getMessage();

                }
            }
        }
        if (!empty($error)) {
            return $error;
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


    public function getWebhookUrls($token)
    {
        $urls = array(
            'config' => new NitropackUrl($this->urlHelper->getUrl('NitroPack/Webhook/Config') . '?token=' . $token),
            'cache_clear' => new NitropackUrl($this->urlHelper->getUrl('NitroPack/Webhook/CacheClear') . '?token=' . $token),
            'cache_ready' => new NitropackUrl($this->urlHelper->getUrl('NitroPack/Webhook/CacheReady') . '?token=' . $token)
        );

        return $urls;
    }


    public function nitroGenerateWebhookToken($siteId)
    {

        return $this->encryptor->hash($this->directoryList->getPath('var'). ":" . $siteId);
    }
}

<?php

namespace NitroPack\NitroPack\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Area;
use Magento\Sitemap\Model\ResourceModel\Sitemap\CollectionFactory;
use Magento\Sitemap\Model\SitemapFactory;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Logger\Logger;

class SitemapHelper extends AbstractHelper
{

    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;
    /**
     * @var StoreManagerInterface
     * */
    protected $storeManager;
    /**
     * @var \Magento\Sitemap\Model\Sitemap
     * */
    protected $sitemap;
    /**
     * @var Emulation
     */
    private $appEmulation;
    /**
     * @var SitemapFactory
     * */
    private $sitemapFactory;
    /**
     * @var CollectionFactory
     */
    private $sitemapCollectionFactory;
    /**
     * @var  \Magento\Store\Model\StoreManagerInterface
     * */
    private $_storeManager;
    /**
     * @var \Magento\Framework\Filesystem\Io\File
     * */
    private $file;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var \Magento\Framework\Filesystem
     * */
    protected $filesystem;
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param Emulation $appEmulation
     * @param Logger $logger
     * @param \Magento\Framework\Filesystem\Io\File $file
     * @param SitemapFactory $sitemapFactory,
     * @param \Magento\Sitemap\Model\Sitemap $sitemap,
     * @param \Magento\Framework\Filesystem $filesystem,
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager,
     * @param \Magento\Sitemap\Model\ResourceModel\Sitemap\CollectionFactory $sitemapCollectionFactory
     * */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        Emulation $appEmulation,
        Logger $logger,
        \Magento\Framework\Filesystem\Io\File $file,
        SitemapFactory $sitemapFactory,
        \Magento\Sitemap\Model\Sitemap $sitemap,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sitemap\Model\ResourceModel\Sitemap\CollectionFactory $sitemapCollectionFactory
    ) {
        parent::__construct($context);
        $this->file = $file;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->sitemapCollectionFactory = $sitemapCollectionFactory;
        $this->appEmulation = $appEmulation;
        $this->_storeManager = $storeManager;
        $this->sitemapFactory = $sitemapFactory;
        $this->sitemap = $sitemap;
    }


    public function getSiteMapPath($storeGroup, $storeGroupCode, $nitroSetting)
    {
        $stores = $this->_storeManager->getStores();
        $storeUrl = '';
        $dataValue = [];
        foreach ($stores as $storesData) {
            if ($storesData->getStoreGroupId() == $storeGroup) {
                if (empty($storeUrl)) {
                    $storeUrl = $this->_storeManager->getStore($storesData->getId())->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                    );
                }
                if($storesData->getIsActive()) {
                    $siteMap = $this->sitemapCollectionFactory->create()->addStoreFilter([$storesData->getId()])->addOrder('sitemap_time','desc')->setPageSize(1)->load();
                    if ($siteMap->getSize() > 0) {
                        foreach ($siteMap  as $sitemapValue) {
                            $sitemapUrl = $sitemapValue->getSitemapUrl(
                                $sitemapValue->getSitemapPath(),
                                $sitemapValue->getSitemapFilename()
                            );
                            $dataValue[] = $sitemapUrl;
                        }
                    } else {
                        $data = [
                            'sitemap_path' => '/media/',
                            'sitemap_filename' => $storesData->getCode() . '.xml',
                            'store_id' => $storesData->getId()
                        ];
                        /** @var \Magento\Sitemap\Model\Sitemap $model */
                        $model = $this->sitemapFactory->create();
                        $model->setData($data);
                        $sitemapUrl = $model->getSitemapUrl($model->getSitemapPath(), $model->getSitemapFilename());
                        $dataValue[] = $sitemapUrl;
                        try {
                            $model->save();
                            $this->appEmulation->startEnvironmentEmulation(
                                $model->getStoreId(),
                                Area::AREA_FRONTEND,
                                true
                            );
                            $model->generateXml();
                            $this->appEmulation->stopEnvironmentEmulation();
                        } catch (\Exception $e) {
                            $this->logger->critical($e->getMessage());
                        }
                    }
                }
            }
        }
        return  $this->generateStoreGroupXml($dataValue,$nitroSetting,$storeUrl,$storeGroupCode);

    }

    public function generateStoreGroupXml($dataValue,$settings,$storeUrl,$storeGroupCode){
        $xmlData = '<?xml version="1.0" encoding="UTF-8"?>';
        $xmlData .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        if ($settings->enabled) {
            foreach ($dataValue as $dataValueItem) {
                $xmlData .= '<sitemap><loc>' . $dataValueItem . '</loc></sitemap>';
            }
        }
        $xmlData .= '</sitemapindex>';
        try {

            $mediaPath = $this->filesystem->getDirectoryRead(
                \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
            )->getAbsolutePath();

            if (!empty($storeGroupCode)) {
                $xmlPath = $mediaPath . '/' . $storeGroupCode . '.xml';

                $this->file->write($xmlPath, $xmlData);
                return $storeUrl . $storeGroupCode . '.xml';
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            return false;
        }
        return false;
    }
}

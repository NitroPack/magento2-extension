<?php

namespace NitroPack\NitroPack\Controller\Adminhtml\Connect;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\DirectoryList;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\AdminFrontendUrl;
use NitroPack\NitroPack\Helper\ApiHelper;
use NitroPack\NitroPack\Helper\NitroPackConfigHelper;
use NitroPack\Url\Url as NitropackUrl;

class Save extends StoreAwareAction
{
    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;
    /**
     * @var AdminFrontendUrl
     * */
    protected $urlHelper;
    /**
     * @var JsonFactory
     * */
    protected $resultJsonFactory;

    protected $request = null;

    protected $siteId = null;
    protected $siteSecret = null;
    protected $errors = array();
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     * */
    protected $fileDriver;
    /**
     * @var DirectoryList
     * */
    protected $directoryList;
    /**
     * @var NitroPackConfigHelper
     * */
    protected $nitroPackConfigHelper;
    /**
     * @var ApiHelper
     * */
    protected $apiHelper;
    /**
     * @param Context $context
     * @param NitroServiceInterface $nitro
     * @param AdminFrontendUrl $urlHelper
     * @param ApiHelper $apiHelper
     * @param NitroPackConfigHelper  $nitroPackConfigHelper
     * @param DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * */
    public function __construct(
        Context                                   $context,
        NitroServiceInterface                     $nitro,
        AdminFrontendUrl                          $urlHelper,
        ApiHelper $apiHelper,
        NitroPackConfigHelper                     $nitroPackConfigHelper,
        DirectoryList                             $directoryList,
        \Magento\Framework\Filesystem\Driver\File $fileDriver
    )
    {
        parent::__construct($context, $nitro);
        $this->nitro = $nitro;
        $this->resultJsonFactory = $this->_objectManager->create(JsonFactory::class);
        $this->urlHelper = $urlHelper;
        $this->nitroPackConfigHelper = $nitroPackConfigHelper;
        $this->request = $this->getRequest();
        $this->fileDriver = $fileDriver;
        $this->apiHelper = $apiHelper;
        $this->directoryList = $directoryList;
    }

    public function nitroExecute()
    {

        if ($this->validateSiteCredentials()) {
            try {
                $this->saveSettings();
                $this->nitro->reload($this->getStoreGroup()->getCode());
                $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
                if ($this->fileDriver->isExists($rootPath . 'notify_disconnection' . '.json')) {
                    $this->fileDriver->deleteFile($rootPath . 'notify_disconnection' . '.json');
                }
                $siteId = $this->nitro->getSettings()->siteId;
                $token = $this->nitro->nitroGenerateWebhookToken($siteId);
                $urls = $this->getWebhookUrls($token);

                foreach ($urls as $type => $url) {
                    $this->nitro->getSdk()->getApi()->setWebhook($type, $url);
                }
                $eventUrl = $this->nitro->integrationUrl('extensionEvent');
                $this->nitro->nitroEvent('connect', $eventUrl, $this->storeGroup);
                $eventSent = $this->nitro->nitroEvent('enable_extension', $eventUrl, $this->storeGroup);
                $this->nitroPackConfigHelper->xMagentoVaryAdd($this->getStoreGroup());



                $this->nitroPackConfigHelper->setBoolean('default_stock',$this->apiHelper->checkDefaultStockAvailable());

                $this->nitroPackConfigHelper->setBoolean('cache_to_login_customer', true);
                $this->nitroPackConfigHelper->persistSettings($this->getStoreGroup()->getCode());
                $this->nitroPackConfigHelper->varnishConfiguredSetup();
                return $this->resultJsonFactory->create()->setData(array(
                    'connected' => true,
                    'redirect' => $this->getUrlWithStore('NitroPack/settings/index', array(
                        'store' => $this->storeGroup->getId()
                    )),
                    'event' => $eventSent
                ));
            } catch (\NitroPack\SDK\ChallengeVerificationException $e) {
                $this->nitro->disconnect($this->getStoreGroup()->getCode());
                $errorMessage = $e->getMessage();
                return $this->resultJsonFactory->create()->setData(array(
                    'connected' => false,
                    'errors' => $errorMessage
                ));
            } catch (\NitroPack\SDK\ChallengeProcessingException $e) {
                $this->nitro->disconnect($this->getStoreGroup()->getCode());
                $errorMessage = $e->getMessage();
                return $this->resultJsonFactory->create()->setData(array(
                    'connected' => false,
                    'errors' => $errorMessage
                ));
            } catch (\NitroPack\SDK\ConfigFetcherException $e) {
                $this->nitro->disconnect($this->getStoreGroup()->getCode());
                $errorMessage = $e->getMessage();
                return $this->resultJsonFactory->create()->setData(array(
                    'connected' => false,
                    'errors' => $errorMessage
                ));
            } catch (\Exception $e) {
                $this->nitro->disconnect($this->getStoreGroup()->getCode());
                if (strpos(strtolower($e->getMessage()), 'not reliable') !== false) {
                    $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
                    $path = $rootPath . 'nitro_cache' . DIRECTORY_SEPARATOR . $siteId;
                    if ($path && !\NitroPack\SDK\Filesystem::isDirEmpty($path)) {
                        \NitroPack\SDK\Filesystem::deleteDir($path);
                    }
                }

                $errorMessage = $e->getMessage();
                return $this->resultJsonFactory->create()->setData(array(
                    'connected' => false,
                    'errors' => $errorMessage
                ));
            }
        } else {
            return $this->resultJsonFactory->create()->setData(array(
                'connected' => false,
                'errors' => implode(',', $this->errors)
            ));
        }
    }

    protected function validateSiteCredentials()
    {
        $siteId = trim($this->request->getPostValue('nitro_site_id', ""));
        $siteSecret = trim($this->request->getPostValue('nitro_site_secret', ""));

        if (!$siteId) {
            $this->errors['nitro_site_id'] = 'Site Id cannot be blank';
        }

        if (!$siteSecret) {
            $this->errors['nitro_site_secret'] = 'Site Secret cannot be blank';
        }

        if (!preg_match("/^([a-zA-Z]{32})$/", $siteId)) {
            $this->errors['nitro_site_id'] = 'Not a valid Site Id';
        }

        if (!preg_match("/^([a-zA-Z0-9]{64})$/", trim($siteSecret))) {
            $this->errors['nitro_site_secret'] = 'Not a valid Site Secret';
        }

        $result = empty($this->errors);
        if ($result) {
            $this->siteId = $siteId;
            $this->siteSecret = $siteSecret;
        }

        return $result;
    }

    protected function saveSettings()
    {
        $this->nitro->setSiteId($this->siteId);
        $this->nitro->setSiteSecret($this->siteSecret);
        $this->nitro->persistSettings($this->storeGroup->getCode());
    }

    protected function getWebhookUrls($token)
    {
        $urls = array(
            'config' => new NitropackUrl($this->urlHelper->getUrl('NitroPack/Webhook/Config') . '?token=' . $token),
            'cache_clear' => new NitropackUrl($this->urlHelper->getUrl('NitroPack/Webhook/CacheClear') . '?token=' . $token),
            'cache_ready' => new NitropackUrl($this->urlHelper->getUrl('NitroPack/Webhook/CacheReady') . '?token=' . $token)
        );

        return $urls;
    }



}

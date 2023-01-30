<?php

namespace NitroPack\NitroPack\Model\NitroPackNotification;

use Magento\Framework\Filesystem\DirectoryList;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\SDK\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class Notifications
{
    /**
     * @var RequestInterface
     **/
    protected $request;
    /**
     * @var DirectoryList
     * */
    protected $directoryList;
    private $cacheTtl = 3600;
    /**
     * @var NitroServiceInterface
     * */
    private $nitro;

    private $notifications;
    /**
     * @var LoggerInterface
     * */
    private $logger;
    /**
     * @var StoreManagerInterface $storeManager
     * */
    protected $storeManager;
    /**
     * @var Json
     * */
    protected $serializer;

    public function __construct(
        NitroServiceInterface $nitro,
        StoreManagerInterface $storeManager,
        DirectoryList $directoryList,
        Json $serializer,
        LoggerInterface $logger,
        RequestInterface $request
    ) {
        $this->serializer = $serializer;
        $this->directoryList = $directoryList;
        $this->logger = $logger;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->nitro = $nitro;
        $storeId = (int)$this->request->getParam('store');
        if ($storeId == 0) {
            $storeId = $this->storeManager->getDefaultStoreView()->getId();
        }
        $store = $this->storeManager->getStore($storeId);

        try {
            $this->nitro->reload($this->storeManager->getGroup($store->getStoreGroupId())->getCode());
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
        $this->notifications = null;
    }

    public function get($type = null)
    {
        if ($this->notifications === null) {
            $this->load();
        }

        if (isset($this->notifications[$this->nitro->getSiteId()])) {
            $result = $this->notifications[$this->nitro->getSiteId()];
            if ($type) {
                return isset($result['notifications'][$type]) ? $result['notifications'][$type] : [];
            } else {
                return $result['notifications'];
            }
        } else {
            return [];
        }
    }

    private function load()
    {
        $this->notifications = [];
        $rootPath = __DIR__ . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR;

        try {
            $rootPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR;
        } catch (\Magento\Framework\Exception\FileSystemException $e) {
            // fallback to using the module directory
        }
        $notificationsFile = $rootPath . 'nitro_system_notifications.json';
        if (Filesystem::fileExists($notificationsFile)) {
            $this->notifications = $this->serializer->unserialize(
                Filesystem::fileGetContents($notificationsFile),
                true
            );
            if (!empty($this->notifications) && isset($this->notifications[$this->nitro->getSiteId()])) {
                $result = $this->notifications[$this->nitro->getSiteId()];
                if ($result['last_modified'] + $this->cacheTtl > time()) { // The cache is still fresh
                    $this->removeExpiredSystemNotifications();
                    return;
                }
            }
        }

        if ($this->nitro->isConnected()) {
            try {
                $result = $this->fetch();
                $this->notifications[$this->nitro->getSiteId()] = [
                    'last_modified' => time(),
                    'notifications' => $result
                ];
                Filesystem::filePutContents($notificationsFile, $this->serializer->serialize($this->notifications));
            } catch (\Exception $e) {
                $this->notifications[$this->nitro->getSiteId(
                )] = [ // We need this entry in order to make use of the cache logic
                    'last_modified' => time(),
                    'error' => $e->getMessage(),
                    'notifications' => []
                ];
                Filesystem::filePutContents($notificationsFile, $this->serializer->serialize($this->notifications));
            }
        }
    }

    private function fetch()
    {
        $notificationsUrl = $this->nitro->integrationUrl('notifications_json');
        $client = new \NitroPack\HttpClient\HttpClient($notificationsUrl);
        $client->setHeader("x-nitro-platform", "magento");
        $client->fetch();
        $resp = $client->getStatusCode() == 200 ? $this->serializer->unserialize($client->getBody(), true) : false;
        return $resp ? $resp['notifications'] : [];
    }

    private function removeExpiredSystemNotifications()
    {
        if (isset($this->notifications[$this->nitro->getSiteId()]['notifications']['system'])) {
            date_default_timezone_set('UTC');
            foreach (
                $this->notifications[$this->nitro->getSiteId()]['notifications']['system'] as $key => $notification
            ) {
                if (strtotime($notification['end_date']) < time()) {
                    unset($this->notifications[$this->nitro->getSiteId()]['notifications']['system'][$key]);
                }
            }
        }
    }
}

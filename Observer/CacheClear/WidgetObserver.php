<?php

namespace NitroPack\NitroPack\Observer\CacheClear;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Widget\Model\Widget\Instance as WidgetInstance;

use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Api\TaggingServiceInterface;
use NitroPack\NitroPack\Observer\CacheClearObserver;
use Psr\Log\LoggerInterface;

class WidgetObserver extends CacheClearObserver
{


    protected $storeId = 0;

    public function __construct(
        NitroServiceInterface $nitro,
        TaggingServiceInterface $tagger,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\Framework\MessageQueue\DefaultValueProvider $defaultQueueValueProvider,
        \Magento\Framework\Serialize\Serializer\Json $json,
        LoggerInterface $logger,
        DeploymentConfig $config
    ) {
        parent::__construct($nitro, $tagger, $request, $storeManager, $logger,$defaultQueueValueProvider, $publisher, $json,$config);
        $this->storeId = $this->request->getParam('store');
        if ($this->storeId == 0) {
            $this->storeId = $this->storeManager->getDefaultStoreView()->getId();
        }
    }

    protected static $eventMap = array(
        'widget_widget_instance_save_commit_after' => 'saved',
        'widget_widget_instance_delete_commit_after' => 'deleted'
    );

    protected $widget; // Magento\Widget\Model\Widget\Instance

    public function prepareData(Observer $observer)
    {
        if (!$this->shouldAutoClear('widgets')) {
            return false;
        }

        $data = $observer->getEvent()->getData();

        if (!isset($data['object']) || !is_a($data['object'], WidgetInstance::class)) {
            return false;
        }

        $this->widget = $data['object'];

        return true;
    }

    public function saved(Observer $observer)
    {
        $widgetName = $this->widget->getTitle();
        if (!$widgetName || $widgetName == '') {
            $widgetName = '#' . $this->widget->getId();
        }
        $rawData = [
            'action' => 'invalidation',
            'type' => 'widget',
            'tag' => 'page',
            'reasonType' => 'widget',
            'storeId' => $this->storeId,
            'reasonEntity' => $widgetName
        ];

        $this->_publisher->publish($this->getTopicName(), $this->_json->serialize($rawData));
        //  $this->invalidateTag('page', 'widget', $widgetName);
        $rawData = [
            'action' => 'invalidation',
            'type' => 'widget',
            'tag' => 'block',
            'reasonType' => 'widget',
            'storeId' => $this->storeId,
            'reasonEntity' => $widgetName
        ];

        $this->_publisher->publish($this->getTopicName(), $this->_json->serialize($rawData));
        // $this->invalidateTag('block', 'widget', $widgetName);
    }

    public function deleted(Observer $observer)
    {
        $widgetName = $this->widget->getTitle();
        if (!$widgetName || $widgetName == '') {
            $widgetName = '#' . $this->widget->getId();
        }
        $rawData = [
            'action' => 'purge_tag',
            'type' => 'widget',
            'tag' => 'page',
            'reasonType' => 'widget',
            'storeId' => $this->storeId,
            'reasonEntity' => $widgetName
        ];
        $this->_publisher->publish($this->getTopicName(), $this->_json->serialize($rawData));
        //$this->purgeTagPageCache('page', 'widget', $widgetName);
        $rawData = [
            'action' => 'purge_tag',
            'type' => 'widget',
            'tag' => 'block',
            'reasonType' => 'widget',
            'storeId' => $this->storeId,
            'reasonEntity' => $widgetName
        ];
        $this->_publisher->publish($this->getTopicName(), $this->_json->serialize($rawData));
        //$this->purgeTagPageCache('block', 'widget', $widgetName);
    }

}

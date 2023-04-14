<?php

namespace NitroPack\NitroPack\Observer\CacheClear;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Cms\Model\Page;

use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Api\TaggingServiceInterface;
use NitroPack\NitroPack\Observer\CacheClearObserver;
use Psr\Log\LoggerInterface;

class PageObserver extends CacheClearObserver
{

    protected static $eventMap = array(
        'cms_page_save_commit_after' => 'saved',
        'cms_page_delete_commit_after' => 'deleted'
    );

    protected $page; // Magento\Cms\Model\Page


    protected $storeId = 0;

    public function __construct(
        NitroServiceInterface $nitro,
        TaggingServiceInterface $tagger,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\Framework\MessageQueue\DefaultValueProvider $defaultQueueValueProvider,
        \Magento\Framework\Serialize\Serializer\Json $json,
        LoggerInterface $logger
    ) {
        parent::__construct($nitro, $tagger, $request, $storeManager, $logger,$defaultQueueValueProvider, $publisher, $json);
        $this->storeId = $this->request->getParam('store');
        if ($this->storeId == 0) {
            $this->storeId = $this->storeManager->getDefaultStoreView()->getId();
        }
    }

    public function prepareData(Observer $observer)
    {
        if (!$this->shouldAutoClear('pages')) {
            return false;
        }

        $data = $observer->getEvent()->getData();

        if (!isset($data['object']) || !is_a($data['object'], Page::class)) {
            return false;
        }

        $this->page = $data['object'];

        return true;
    }

    public function saved(Observer $observer)
    {
        $tag = $this->tagger->getPageTag($this->page);
        $pageName = $this->page->getTitle();
        if (!$pageName || $pageName == '') {
            $pageName = '#' . $this->page->getId();
        }
        $rawData = [
            'action' => 'invalidation',
            'type' => 'page',
            'tag' => $tag,
            'reasonType' => 'page',
            'storeId' => $this->storeId,
            'reasonEntity' => $pageName
        ];

        $this->_publisher->publish($this->defaultQueueValueConnection =='amqp' ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB, $this->_json->serialize($rawData));
        // $this->invalidateTag($tag, 'page', $pageName);
    }

    public function deleted(Observer $observer)
    {
        $tag = $this->tagger->getPageTag($this->page);
        $pageName = $this->page->getTitle();
        if (!$pageName || $pageName == '') {
            $pageName = '#' . $this->page->getId();
        }
        $rawData = [
            'action' => 'purge_tag',
            'type' => 'page',
            'tag' => $tag,
            'reasonType' => 'page',
            'storeId' => $this->storeId,
            'reasonEntity' => $pageName
        ];

        $this->_publisher->publish($this->defaultQueueValueConnection =='amqp' ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB, $this->_json->serialize($rawData));
        //$this->purgeTagComplete($tag, 'page', $pageName);
    }

}

<?php

namespace NitroPack\NitroPack\Observer\CacheClear;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Cms\Model\Block;

use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Api\TaggingServiceInterface;
use NitroPack\NitroPack\Observer\CacheClearObserver;
use Psr\Log\LoggerInterface;

class BlockObserver extends CacheClearObserver
{

    protected $storeId = 0;

    protected static $eventMap = array(
        'cms_block_save_commit_after' => 'saved',
        'cms_block_delete_commit_after' => 'deleted'
    );

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

    protected $block; // Magento\Cms\Model\Block

    public function prepareData(Observer $observer)
    {
        if (!$this->shouldAutoClear('blocks')) {
            return false;
        }

        $data = $observer->getEvent()->getData();

        if (!isset($data['object']) || !is_a($data['object'], Block::class)) {
            return false;
        }

        $this->block = $data['object'];

        return true;
    }

    public function saved(Observer $observer)
    {
        $tag = $this->tagger->getBlockTag($this->block);
        $blockName = $this->block->getTitle();
        if (!$blockName || $blockName == '') {
            $blockName = '#' . $this->block->getId();
        }
        $rawData = [
            'action' => 'invalidation',
            'type' => 'block',
            'tag' => $tag,
            'reasonType' => 'block',
            'storeId' => $this->storeId,
            'reasonEntity' => $blockName
        ];
        $this->_publisher->publish($this->defaultQueueValueConnection =='amqp' ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB , $this->_json->serialize($rawData));
        //  $this->invalidateTag($tag, 'block', $blockName);
    }

    public function deleted(Observer $observer)
    {
        $tag = $this->tagger->getBlockTag($this->block);
        $blockName = $this->block->getTitle();
        if (!$blockName || $blockName == '') {
            $blockName = '#' . $this->block->getId();
        }
        $rawData = [
            'action' => 'purge_tag',
            'type' => 'block',
            'tag' => $tag,
            'reasonType' => 'block',
            'storeId' => $this->storeId,
            'reasonEntity' => $blockName
        ];

        $this->_publisher->publish($this->defaultQueueValueConnection =='amqp' ? self::TOPIC_NAME_AMQP : self::TOPIC_NAME_DB , $this->_json->serialize($rawData));
        // $this->purgeTagComplete($tag, 'block', $blockName);
    }

}

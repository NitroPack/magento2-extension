<?php
namespace NitroPack\NitroPack\Controller\Adminhtml\Cache;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Api\NitroServiceInterface;

class Purge extends StoreAwareAction {
	protected $resultJsonFactory;

	protected $nitro;

	public function __construct(
		Context $context,
		NitroServiceInterface $nitro
	) {
		parent::__construct($context, $nitro);
		$this->resultJsonFactory = $this->_objectManager->create(JsonFactory::class);
		$this->nitro = $nitro;
	}

	protected function nitroExecute() {
		try {
			$purgeResult = $this->nitro->purgeCache(null, null, \NitroPack\SDK\PurgeType::COMPLETE, 'Manual cache purge via Magento Dashboard.');
			$resultMsg = $purgeResult ? 'Cache was purged successfully' : 'Cache was NOT purged successfully';
			return $this->resultJsonFactory->create()->setData(array(
				'purged' => $purgeResult,
				'message' => $resultMsg
			));
		} catch (\Exception $e) {
			return $this->resultJsonFactory->create()->setData(array(
				'purged' => false,
				'message' => 'Cache was NOT purged successfully: ' . $e->getMessage()
			));
		}
	}
}
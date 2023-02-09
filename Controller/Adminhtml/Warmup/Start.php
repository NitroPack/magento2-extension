<?php
namespace NitroPack\NitroPack\Controller\Adminhtml\Warmup;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Api\NitroServiceInterface;

class Start extends StoreAwareAction {
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
			$stats = $this->nitro->getApi()->getWarmupStats();
			$this->nitro->getApi()->enableWarmup();

			if ($stats['pending'] == 0) {
				$this->nitro->getApi()->runWarmup();
			}
			return $this->resultJsonFactory->create()->setData(array(
				'success' => true
			));
		} catch (\Exception $e) {
			return $this->resultJsonFactory->create()->setData(array(
				'success' => false
			));
		}
	}
}

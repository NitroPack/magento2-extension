<?php
namespace NitroPack\NitroPack\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface;

use NitroPack\NitroPack\Api\TaggingServiceInterface;

class CacheTagObserver implements ObserverInterface {
    /**
     * @var \NitroPack\NitroPack\Api\TaggingServiceInterface
     * */
	protected $tagger;
    /**
     * @var \Magento\Framework\App\RequestInterface
     * */
	protected $request;
    /**
     * @var \Psr\Log\LoggerInterface
     * */
	protected $logger;

	static $loggedRequest = false;

	protected static $observersEnabled = true;
    /**
     * @var StoreManagerInterface
     * */
    protected $storeManager;

	public function __construct(
		TaggingServiceInterface $tagger,
		RequestInterface $request,
		LoggerInterface $logger,
        StoreManagerInterface $storeManager
	) {
		$this->tagger = $tagger;
		$this->request = $request;
		$this->logger = $logger;
        $this->storeManager = $storeManager;

    }

	public function execute(Observer $observer) {
		if (!static::$observersEnabled) {
			return;
		}

		$eventName = $observer->getEvent()->getName();
		$callbackMethod = $this->getEventCallback($eventName);

		if ($callbackMethod && method_exists($this, $callbackMethod) && $this->prepareData($observer)) {
			call_user_func_array(array($this, $callbackMethod), array($observer));
		}
	}

	public static function enableObservers() {
		static::$observersEnabled = true;
	}

	public static function disableObservers() {
		static::$observersEnabled = false;
	}

	protected function getEventCallback($eventName) {
		if (isset(static::$eventMap[$eventName])) {
			return static::$eventMap[$eventName];
		}
		return null;
	}

	protected function prepareData(Observer $observer) {
		return true;
	}

	protected function logEventData(Observer $observer) {
		if (!self::$loggedRequest) {
			$this->logger->debug('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~`');
			$this->logger->debug($_SERVER['REQUEST_URI']);
			self::$loggedRequest = true;
		}
		$eventData = $observer->getEvent()->getData();
		$this->logger->debug('============');
		$this->logger->debug('Event: ' . $observer->getEvent()->getName());
		foreach ($eventData as $key => $datum) {
			if (is_object($datum)) {
				$this->logger->debug('    ' . $key . ': ' . get_class($datum));
			} else {
				if (is_string($datum) || is_numeric($datum)) {
					$this->logger->debug('    ' . $key . ': ' . gettype($datum) . ':= ' . $datum);
				} else {
					$this->logger->debug('    ' . $key . ': ' . gettype($datum));
				}
			}
		}
	}

    public function getRootCategoryId(): int
    {
        // get store group id for current store
        $storeGroupId = $this->storeManager->getStore()->getStoreGroupId();
        // get root category id
        return $this->storeManager->getGroup($storeGroupId)->getRootCategoryId();
    }

}

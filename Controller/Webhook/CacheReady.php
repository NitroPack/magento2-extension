<?php

namespace NitroPack\NitroPack\Controller\Webhook;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use NitroPack\NitroPack\Model\FullPageCache\PurgeInterface;


class CacheReady extends WebhookController
{
    /**
     * @var  PurgeInterface
     * */
    protected $purgeInterface;
    /**
     * @var ScopeConfigInterface
     **/
    protected $_scopeConfig;

    /**
     * @param Context $context
     * @param PurgeInterface $purgeInterface
     * @param ScopeConfigInterface $_scopeConfig
     * */
    public function __construct(Context $context, PurgeInterface $purgeInterface, ScopeConfigInterface $_scopeConfig)
    {
        $this->_scopeConfig = $_scopeConfig;
        $this->purgeInterface = $purgeInterface;
        parent::__construct($context);
    }

    public function execute()
    {
        if ($url = $this->getRequest()->getParam('url', false)) {
            $this->nitro->reload(null, $url);
            $this->nitro->hasRemoteCache('', false);
            $this->purgeInterface->purge($url);

        }
        return $this->textResponse('ok');
    }

}

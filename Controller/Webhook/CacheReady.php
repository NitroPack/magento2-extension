<?php
/**
 * NitroPack
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the nitropack.io license that is
 * available through the world-wide-web at this URL:
 * https://github.com/NitroPack/magento2-extension/blob/716247d40d2de7b84f222c6a93761d87b6fe5b7b/LICENSE
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Site Optimization
 * @subcategory Performance
 * @package     NitroPack_NitroPack
 * @author      NitroPack Inc.
 * @copyright   Copyright (c) NitroPack (https://www.nitropack.io/)
 * @license     https://github.com/NitroPack/magento2-extension/blob/716247d40d2de7b84f222c6a93761d87b6fe5b7b/LICENSE
 */
namespace NitroPack\NitroPack\Controller\Webhook;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use NitroPack\NitroPack\Model\FullPageCache\PurgeInterface;

/**
 * Class CacheReady - Controller CacheReady for NitroPack Webhook
 * @extends \Magento\Framework\App\Action\Action
 * @package NitroPack\NitroPack\Controller\Webhook
 * @since 2.0.0
 */
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

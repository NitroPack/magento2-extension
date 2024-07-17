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
use NitroPack\SDK\PurgeType;
use Magento\Framework\App\RequestInterface;
/**
 * Class CacheClear - Controller CacheClear for NitroPack Webhook
 * @extends \Magento\Framework\App\Action\Action
 * @package NitroPack\NitroPack\Controller\Webhook
 * @since 2.0.0
 */
class CacheClear extends WebhookController
{

    /**
     * @var RequestInterface
     * */
    protected $request;

    /**
     * @var PurgeInterface
     * */
    protected $purgeInterface;

    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param ScopeConfigInterface $_scopeConfig
     * @param PurgeInterface $purgeInterface
     * */
    public function __construct(Context $context, RequestInterface $request, ScopeConfigInterface $_scopeConfig,PurgeInterface $purgeInterface)
    {

        $this->request = $request;
        $this->purgeInterface = $purgeInterface;
        parent::__construct($context);
    }

    const REASON_MANUAL_PURGE_URL = "Manual purge of the link %s from the NitroPack.io Dashboard.";
    const REASON_MANUAL_PAGE_CACHE_ONLY_ALL = "Manual page cache clearing of all store pages from the NitroPack.io Dashboard.";

    public function execute()
    {
        if ($url = $this->getRequest()->getParam('url', false)) {
            if (!is_array($url)) {
                $this->purgeSingleUrl([$url]);

            } else {
                $this->purgeSingleUrl($url);
            }
            return $this->textResponse('ok');
        } else {
            $this->nitro->purgeLocalCache(true);
            if(isset($_SERVER['HTTP_X_VARNISH'])){
                $this->purgeInterface->purge();
            }
        }
        return $this->textResponse('ok');
    }


    public function purgeSingleUrl($url)
    {
        foreach ($url as $urlValue) {
            $this->nitro->purgeCache(
                $urlValue,
                null,
                PurgeType::LIGHT_PURGE,
                sprintf(self::REASON_MANUAL_PURGE_URL, $urlValue)
            );
                $this->purgeInterface->purge($urlValue);

        }
    }
}

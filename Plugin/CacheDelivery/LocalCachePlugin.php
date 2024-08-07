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
namespace NitroPack\NitroPack\Plugin\CacheDelivery;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;

use Magento\Framework\App\Response\Http as ResponseHttp;
use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Helper\FastlyHelper;
use NitroPack\NitroPack\Observer\CacheTagObserver;

/**
 * Class LocalCachePlugin - Main Plugin Local Cache Plugin deliver Cache
 * @package NitroPack\NitroPack\Plugin\CacheDelivery
 * @since 2.0.0
 * */
class LocalCachePlugin
{
    // Checks if there is local cache for the current request as soon as possible. Executed before Magento\Framework\App\FrontController::dispatch
    public const XML_VARNISH_PAGECACHE_NITRO_ENABLED = 'system/full_page_cache/varnish_enable';
    public const XML_FASTLY_PAGECACHE_ENABLE_NITRO = 'system/full_page_cache/enable_nitropack';
    public const XML_PAGECACHE_TTL = 'system/full_page_cache/ttl';
    protected $nitro = null;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     * */
    protected $objectManager;
    /**
     * @var ScopeConfigInterface
     * */
    protected $_scopeConfig;
    /**
     * @var \Magento\Framework\App\Http\Context
     */
    private $context;
    /**
     * @var   \Magento\Framework\App\Http\ContextFactory
     * */
    private $contextFactory;
    /**
     * @var \Magento\Framework\App\Response\HttpFactory
     * */
    private $httpFactory;
    /**
     * @var FastlyHelper
     * */
    protected $fastlyHelper;
    /**
     * @param NitroServiceInterface $nitro
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\Http\Context|null $context
     * @param ScopeConfigInterface $_scopeConfig
     * @param FastlyHelper $fastlyHelper
     * @param \Magento\Framework\App\Http\ContextFactory|null $contextFactory
     * @param \Magento\Framework\App\Response\HttpFactory|null $httpFactory
     * */
    public function __construct(
        NitroServiceInterface                       $nitro,
        \Magento\Framework\ObjectManagerInterface   $objectManager,
        ScopeConfigInterface                        $_scopeConfig,
        FastlyHelper $fastlyHelper,
        \Magento\Framework\App\Http\Context         $context = null,
        \Magento\Framework\App\Http\ContextFactory  $contextFactory = null,
        \Magento\Framework\App\Response\HttpFactory $httpFactory = null
    )
    {
        $this->_scopeConfig = $_scopeConfig;
        $this->objectManager = $objectManager;
        $this->fastlyHelper = $fastlyHelper;
        $this->nitro = $nitro;
        $this->context = $context ?? ObjectManager::getInstance()->get(\Magento\Framework\App\Http\Context::class);
        $this->contextFactory = $contextFactory ?? ObjectManager::getInstance()->get(
            \Magento\Framework\App\Http\ContextFactory::class
        );
        $this->httpFactory = $httpFactory ?? ObjectManager::getInstance()->get(
            \Magento\Framework\App\Response\HttpFactory::class
        );
    }

    // The RequestInterface below is not injected, it's passed to the dispatch function. We can inject it in the controller, but it is not yet routed, so we should use the one passed in
    public function aroundDispatch(
        \Magento\Framework\App\FrontController $subject,
        \Closure                               $proceed,
        RequestInterface                       $request
    )
    {


        if (headers_sent() || NitroService::isANitroRequest()) {
            return $proceed($request);
        }

        if (!$this->nitro->isConnected() || !$this->nitro->isEnabled() ||
            is_null(
                $this->_scopeConfig->getValue(NitroService::FULL_PAGE_CACHE_NITROPACK)
            ) || !in_array($this->_scopeConfig->getValue(
                NitroService::FULL_PAGE_CACHE_NITROPACK
            ),[NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE,NitroService::FASTLY_CACHING_APPLICATION_VALUE])){
            header('X-Nitro-Disabled: 1');
            return $proceed($request);
        }
        //Check NitroPack With Fastly Disable

        if($this->fastlyHelper->isFastlyAndNitroDisable()){
            header('X-Nitro-Disabled: 1');
            return $proceed($request);
        }

        if($this->nitro->isSafeModeEnabled() && (!$request->getParam('testnitro')) ){
            header('X-Nitro-Disabled: 1');
            return $proceed($request);
        }

        header('X-Nitro-Cache: MISS');
        header('X-Nitro-Intergration-Version:' . $this->nitro->extensionVersion());
        header('X-Nitro-Sdk-Version:' . $this->nitro->sdkVersion());
        if($request->getFrontName()=='checkout'){
            header('X-Nitro-Disabled: 1', true);
            return $proceed($request);
        }
        CacheTagObserver::disableObservers();

        if ($this->nitro->isCustomerLogin() && !$this->nitro->isCustomerLoginEnable() ) { // Magento specific checks if the request can be cached

            header('X-Nitro-Disabled: 1', true);
            CacheTagObserver::enableObservers();
            return $proceed($request);
        }

        try {
            if ($request->isGet() || $request->isHead()) {

                if ($this->nitro->hasLocalCache()) {
                    header('X-Nitro-Cache: HIT', true);
                    //CHECK VARNISH ENABLE && VARNISH IS CONFIGURE
                    if (!is_null(
                            $this->_scopeConfig->getValue(self::XML_VARNISH_PAGECACHE_NITRO_ENABLED)
                        ) && $this->_scopeConfig->getValue(
                            self::XML_VARNISH_PAGECACHE_NITRO_ENABLED
                        ) == 1 && isset($_SERVER['HTTP_X_VARNISH'])) {
                        $pageCacheTTL = !is_null(
                            $this->_scopeConfig->getValue(self::XML_PAGECACHE_TTL)
                        ) ? $this->_scopeConfig->getValue(self::XML_PAGECACHE_TTL) : 86400;
                        header('cache-control: max-age=' . $pageCacheTTL . ', public, s-maxage=' . $pageCacheTTL, true);
                        header('x-magento-tags: ', true);
                    }
                    //CHECK FASTLY ENABLE && FASTLY IS CONFIGURE
                    if (!is_null(
                            $this->_scopeConfig->getValue(self::XML_FASTLY_PAGECACHE_ENABLE_NITRO)
                        ) && $this->_scopeConfig->getValue(
                            self::XML_FASTLY_PAGECACHE_ENABLE_NITRO
                        ) == 1 && isset($_SERVER['HTTP_X_VARNISH']) ) {
                        $pageCacheTTL = !is_null(
                            $this->_scopeConfig->getValue(self::XML_PAGECACHE_TTL)
                        ) ? $this->_scopeConfig->getValue(self::XML_PAGECACHE_TTL) : 86400;
                        header('cache-control: max-age=' . $pageCacheTTL . ', public, s-maxage=' . $pageCacheTTL, true);
                    }
                    $content = $this->nitro->pageCache->returnCacheFileContent();
                    $responseData = [
                        'content' => $content[1],
                        'status_code' => 200,
                        'headers' =>  $content[0],
                        'context' => $this->context->toArray()

                    ];
                    return $this->buildResponse($responseData);

                } else {
                    if ($this->nitro->getSdk()->getHealthStatus() == "SICK") {
                        //TODO  LOG FILE WILL CREATED self::logException(new \Exception("Health status = SICK."));
                        header("X-Nitro-Cache: MISS");
                        header("X-Nitro-Cache-From: SICK");
                        CacheTagObserver::enableObservers();
                        return $proceed($request);
                    }
                }
            }
        } catch (\Exception $exception) {

            header('X-Nitro-Disabled: 1', true);
            CacheTagObserver::enableObservers();
            return $proceed($request);
        }

        CacheTagObserver::enableObservers();
        return $proceed($request);
    }

    /**
     * Build response using response data.
     *
     * @param array $responseData
     * @return \Magento\Framework\App\Response\Http
     */
    private function buildResponse($responseData)
    {


        $context = $this->contextFactory->create(
            [
                'data' => $responseData['context']['data'],
                'default' => $responseData['context']['default']
            ]
        );

        $response = $this->httpFactory->create(
            [
                'context' => $context
            ]
        );
        $response->setStatusCode(200);
        $response->setContent($responseData['content']);

        foreach ($responseData['headers'] as $headerKey => $headerValue) {

            $response->setHeader($headerValue['name'], $headerValue['value'], true);

        }


        return $response;
    }
}

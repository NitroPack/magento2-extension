<?php

namespace NitroPack\NitroPack\Plugin\CacheDelivery;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;

use NitroPack\NitroPack\Api\NitroService;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Observer\CacheTagObserver;

class LocalCachePlugin
{
    // Checks if there is local cache for the current request as soon as possible. Executed before Magento\Framework\App\FrontController::dispatch
    public const XML_VARNISH_PAGECACHE_NITRO_ENABLED = 'system/full_page_cache/varnish_enable';
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

    public function __construct(
        NitroServiceInterface $nitro,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        ScopeConfigInterface $_scopeConfig
    ) {
        $this->_scopeConfig = $_scopeConfig;
        $this->objectManager = $objectManager;
        $this->nitro = $nitro;
    }

    // The RequestInterface below is not injected, it's passed to the dispatch function. We can inject it in the controller, but it is not yet routed, so we should use the one passed in
    public function aroundDispatch(
        \Magento\Framework\App\FrontController $subject,
        \Closure $proceed,
        RequestInterface $request
    ) {
        if (headers_sent() || NitroService::isANitroRequest()) {
            return $proceed($request);
        }


        if (!$this->nitro->isConnected() || !$this->nitro->isEnabled() || is_null(
                $this->_scopeConfig->getValue(\NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK)
            ) || $this->_scopeConfig->getValue(
                \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK
            ) != \NitroPack\NitroPack\Api\NitroService::FULL_PAGE_CACHE_NITROPACK_VALUE) {

            header('X-Nitro-Disabled: 1');
            return $proceed($request);
        }

        header('X-Nitro-Cache: MISS');
        header('X-Nitro-Intergration-Version:' . $this->nitro->extensionVersion());
        header('X-Nitro-Sdk-Version:' . $this->nitro->sdkVersion());

        CacheTagObserver::disableObservers();

        if (!$this->nitro->isCacheable()) { // Magento specific checks if the request can be cached

            header('X-Nitro-Disabled: 1', true);
            CacheTagObserver::enableObservers();
            return $proceed($request);
        }
        try{

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
                $this->nitro->pageCache->readfile();
                exit;
//            $context = $this->objectManager->create(\Magento\Framework\View\Element\Template\Context::class);
//            $layoutFactory = $this->objectManager->create(\Magento\Framework\View\LayoutFactory::class);
//            $template = '';
//
//            $layoutReaderPool = $this->objectManager->create(\Magento\Framework\View\Layout\ReaderPool::class);
//            $translateInline = $this->objectManager->create(\Magento\Framework\Translate\InlineInterface::class);
//            $layoutBuilderFactory = $this->objectManager->create(\Magento\Framework\View\Layout\BuilderFactory::class);
//            $generatorPool = $this->objectManager->create(\Magento\Framework\View\Layout\GeneratorPool::class);
//            $pageConfigRendererFactory = $this->objectManager->create(
//                \Magento\Framework\View\Page\Config\RendererFactory::class
//            );
//            $pageLayoutReader = $this->objectManager->create(\Magento\Framework\View\Page\Layout\Reader::class);
//            $data = new \Magento\Framework\View\Result\Page(
//                $context,
//                $layoutFactory,
//                $layoutReaderPool,
//                $translateInline,
//                $layoutBuilderFactory,
//                $generatorPool,
//                $pageConfigRendererFactory,
//                $pageLayoutReader,
//                $template
//            );
                return '';
            } else {
                if ($this->nitro->getSdk()->getHealthStatus() == "SICK") {
                    //TODO  LOG FILE WILL CREATED self::logException(new \Exception("Health status = SICK."));
                    header("X-Nitro-Cache: MISS");
                    header("X-Nitro-Cache-From: SICK");
                    CacheTagObserver::enableObservers();
                    return $proceed($request);
                }
            }
        }catch (\Exception $exception){

            header('X-Nitro-Disabled: 1', true);
            CacheTagObserver::enableObservers();
            return $proceed($request);
        }

        CacheTagObserver::enableObservers();
        return $proceed($request);
    }

}

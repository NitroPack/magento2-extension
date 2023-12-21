<?php

namespace NitroPack\NitroPack\Model\Layout;

use Magento\Framework\App\MaintenanceMode;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Layout;
use Magento\PageCache\Model\Config;
use Magento\PageCache\Model\Spi\PageCacheTagsPreprocessorInterface;
class LayoutPlugin
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var ResponseInterface
     */
    private $response;
    /**
     * @var PageCacheTagsPreprocessorInterface
     */
    private $pageCacheTagsPreprocessor;
    /**
     * @var MaintenanceMode
     */
    private $maintenanceMode;
    /**
     * @var RequestInterface
     * */
    private $request;
    /**
     * @param ResponseInterface $response
     * @param Config $config
     * @param MaintenanceMode $maintenanceMode
     * @param PageCacheTagsPreprocessorInterface|null $pageCacheTagsPreprocessor
     */
    public function __construct(
        ResponseInterface $response,
        Config $config,
        RequestInterface $request,
        MaintenanceMode $maintenanceMode,
        ?PageCacheTagsPreprocessorInterface $pageCacheTagsPreprocessor = null
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->config = $config;
        $this->maintenanceMode = $maintenanceMode;
        $this->pageCacheTagsPreprocessor = $pageCacheTagsPreprocessor
            ?? ObjectManager::getInstance()->get(PageCacheTagsPreprocessorInterface::class);
        //  parent::__construct($response,$config,$maintenanceMode,$pageCacheTagsPreprocessor);
    }
    /**
     * Set appropriate Cache-Control headers.
     *
     * We have to set public headers in order to tell Varnish and Builtin app that page should be cached
     *
     * @param Layout $subject
     * @return void
     */
    public function afterGenerateElements(Layout $subject)
    {
        if ($subject->isCacheable() && !$this->maintenanceMode->isOn() && $this->config->isEnabled()) {
            if($this->request->getFrontName()=='checkout'){
            }else{
                $this->response->setPublicHeaders($this->config->getTtl());
            }
        }
    }
    public function afterGetOutput(Layout $subject, $result)
    {

        if (!$subject->isCacheable() ) {
            $this->response->setHeader('X-Magento-Tags-Disable',1);
            $this->response->setHeader('X-Nitro-Disabled', 1);
        }
        return $result;
    }

}

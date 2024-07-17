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
namespace NitroPack\NitroPack\Plugin\Layout;

use Magento\Framework\App\MaintenanceMode;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Layout;
use Magento\PageCache\Model\Config;
use Magento\PageCache\Model\Spi\PageCacheTagsPreprocessorInterface;

/**
 * Class LayoutPlugin - Layout Plugin
 * @package NitroPack\NitroPack\Plugin\Layout
 * @since 2.0.0
 * */
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
        MaintenanceMode $maintenanceMode
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->config = $config;
        $this->maintenanceMode = $maintenanceMode;
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
        }
        return $result;
    }

}

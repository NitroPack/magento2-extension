<?php

namespace NitroPack\NitroPack\Block;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\RequestInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;

class RemoteCache extends Template
{
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $response;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     * */
    protected $storeManager;

    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;
    /**
     * @var \Magento\Framework\UrlInterface
     * */
    protected $urlInterface;

    /**
     * @param Context $context ,
     * @param \Magento\Framework\App\ResponseInterface $response
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param RequestInterface $request
     * @param NitroServiceInterface $nitro ,
     * @param \Magento\Framework\UrlInterface $urlInterface ,
     * */
    public function __construct(
        Context $context,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        RequestInterface $request,
        NitroServiceInterface $nitro,
        \Magento\Framework\UrlInterface $urlInterface,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->response = $response;
        $this->nitro = $nitro;
        $this->urlInterface = $urlInterface;
        $this->storeManager = $storeManager;
        $this->request = $request;
    }


    public function getRoute()
    {
        return $this->request->getFullActionName();
    }

    public function getNitroLayout()
    {
        $store = $this->storeManager->getStore();
        $storeViewId = $store->getId();
        $storeId = $store->getStoreGroupId();
        $websiteId = $store->getWebsiteId();
        $route = $this->request->getFullActionName();
        return $websiteId . '_' . $storeId . '_' . $storeViewId . '_' . $route;
    }

    public function getCurrentUrl()
    {
        return $this->urlInterface->getCurrentUrl();
    }

    public function checkNitroHeaderCacheIsMiss()
    {
        if ($this->response->getHttpResponseCode() == 200) {
            foreach (headers_list() as $headers) {
                $values = explode(":", $headers);
                if (trim(strtolower($values[0])) == 'x-nitro-cache' && trim($values[1]) == 'MISS') {
                    return true;
                }
            }
        }
        return false;
    }

    public function getEnabled()
    {
        $settings = $this->nitro->getSettings();
        return $settings->enabled;
    }

    public function getSiteId()
    {
        $settings = $this->nitro->getSettings();
        return $settings->siteId;
    }

    public function getSiteSecret()
    {
        $settings = $this->nitro->getSettings();
        return $settings->siteSecret;
    }

    public function getStoreCode()
    {
        $store = $this->storeManager->getStore();
        return $store->getCode();
    }
}

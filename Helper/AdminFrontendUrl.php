<?php

namespace NitroPack\NitroPack\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Url;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class AdminFrontendUrl extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     * */
    protected $storeManager;
    /**
     * @var Url
     * */
    protected $urlHelper;
    /**
     * @var Store
     * */
    protected $store;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Url $urlHelper
     * @param Store $store
     * */
    public function __construct(Context $context, StoreManagerInterface $storeManager, Url $urlHelper, Store $store)
    {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->urlHelper = $urlHelper;
        $this->store = $store;
    }

    // When used in the admin, returns a store-aware frontend URL, stripping the session ID if necessary.
    public function getUrl($route = null, $routeParams = null)
    {
        $storeGroupId = (int)$this->_request->getParam('group', 0);
        if ($storeGroupId == 0) {
            $storeGroupId = $this->storeManager->getGroup()->getId();
        }
        $storeGroup = $this->storeManager->getGroup($storeGroupId);
        $store = $this->storeManager->getStore($storeGroup->getDefaultStoreId());
        $this->urlHelper->setScope($store->getCode());
        $url = html_entity_decode($this->urlHelper->getUrl($route, $routeParams));
        $parsed = parse_url($url);
        if (!empty($parsed['query']) && preg_match('/(&?)(SID=[a-zA-Z0-9]*)(&?)/', $parsed['query'], $matches)) {
            $replace = '';
            if ($matches[1] == '&' && $matches[3] == '&') {
                $replace = '$';
            }
            $parsed['query'] = preg_replace('/(&?)(SID=[a-zA-Z0-9]*)(&?)/', $replace, $parsed['query']);
        }

        if (isset($parsed['query']) && $parsed['query'] == '') {
            unset($parsed['query']);
        }

        return $this->joinParsedUrl($parsed);
    }

    protected function joinParsedUrl($parsed)
    {
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
        $host = isset($parsed['host']) ? $parsed['host'] : '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $user = isset($parsed['user']) ? $parsed['user'] : '';
        $pass = isset($parsed['pass']) ? ':' . $parsed['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsed['path']) ? $parsed['path'] : '';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
        return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
    }

}

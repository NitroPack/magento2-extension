<?php

namespace NitroPack\NitroPack\Plugin\RemoveGetParam;

use Magento\Store\Api\Data\StoreInterface;

class StoreSwitcher
{
    public function afterSwitch(
        \Magento\Store\Model\StoreSwitcher $subject,
        $targetUrl,
        StoreInterface $fromStore,
        StoreInterface $targetStore,
        string $redirectUrl
    ): string {


        return $this->removeParam($targetUrl, 'ignorenitro');
    }

    function removeParam($url, $param) {
        $url = preg_replace('/(&|\?)'.preg_quote($param).'=[^&]*$/', '', $url);
        $url = preg_replace('/(&|\?)'.preg_quote($param).'=[^&]*&/', '$1', $url);
        return $url;
    }
}

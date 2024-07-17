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

namespace NitroPack\NitroPack\Block\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use NitroPack\NitroPack\Block\SettingsBlock;
use NitroPack\NitroPack\Model\System\Message\VarnishMismatchNotification;
use NitroPack\NitroPack\Model\System\Message\RedisFullPageNotification;
/**
 * Class GeneralBlock - Block for the NitroPack Each Widget admin dashboard
 * @block
 * @extends SettingsBlock
 * @package NitroPack\NitroPack\Block\Dashboard
 * @since 3.0.0
 */
class GeneralBlock extends SettingsBlock
{
    /**
     * @var Repository
     */
    protected $assetRepository;
    /**
     * @var RedisFullPageNotification
     */
    public $redisFullPageNotification;
    /**
     * @var VarnishMismatchNotification
     */
    public $varnishMismatchNotification;
    public function __construct(Context               $context, // required as part of the Magento\Backend\Block\Template constructor
                                NitroServiceInterface $nitro, // dependency injection'ed
                                UrlInterface          $backendUrl, // dependency injection'ed
                                StoreManagerInterface $storeManager, // dependency injection'ed
                                RequestInterface      $request, // dependency injection'ed
                                ScopeConfigInterface  $scopeConfig, // dependency injection'ed
                                TypeListInterface     $cacheTypeList, // dependency injection'ed
                                Store                 $store, // dependency injection'ed
                                Repository            $assetRepository,
                                RedisFullPageNotification $redisFullPageNotification,
                                VarnishMismatchNotification $varnishMismatchNotification,
                                array                 $data = [] // required as part of the Magento\Backend\Block\Template constructor
    )
    {
        $this->redisFullPageNotification = $redisFullPageNotification;
        $this->assetRepository = $assetRepository;
        $this->varnishMismatchNotification = $varnishMismatchNotification;
        parent::__construct($context, $nitro, $backendUrl, $storeManager, $request, $scopeConfig, $cacheTypeList, $store, $data);
    }


    public function getImage($imageName)
    {
        return $this->assetRepository->getUrl("NitroPack_NitroPack::img/$imageName");
    }
    /**
     * Get the URL to dismiss the message
     *
     * @return string
     */
    public function getDismissUrl()
    {
        return $this->getBackendUrl('NitroPack/System/Dismiss', true, true);
    }

    /**
     * Get the URL to Enable Redis Full Page Cache  the message
     * @return string
     */
    public function getEnableRedisPageCacheUrl()
    {
        return $this->getBackendUrl('NitroPack/System/EnableRedisFullPageCache', true, true);
    }
}

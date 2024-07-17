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

namespace NitroPack\NitroPack\Api;
/**
 * Interface NitroServiceInterface
 * @api
 * @package NitroPack\NitroPack\Api
 * @since 2.0.0
 */
interface NitroServiceInterface {

	public function extensionVersion();
	public function sdkVersion();

	public function reload($storeGroupCode=null);
	public function disconnect($storeGroupCode=null);

	public function isConnected();
	public function isEnabled();
	public function isSafeModeEnabled();
	public function isCustomerLogin(); // checks that can be done before the layout is known
	public function isCustomerLoginEnable(); // checks that can be done before the layout is known
	public function isCachableRoute($route); // checks that can only be done after the request has been routed, $route is the ->getFullActionName() of the Magento\Framework\App\Request\Http that is about to be executed, typically module_controller_action
    public function isCheckCartOrCustomerRoute($route); // checks that can only be done after the request has been routed,$route is the ->getFullActionName() of the Magento\Framework\App\Request\Http that is about to be executed, typically module_controller_action
	public function getSettings();
	public function setWarmupSettings($enabled, $pageTypes, $currencies);
	public function getBuiltInPageTypeRoutes();

	public function getSiteId();
	public function setSiteId($newSiteId);
    public function setVariableValue($variable,$value);
    public function getXMagentoVary();
    public function setXMagentoVary($data);

	public function getSiteSecret();
	public function setSiteSecret($newSiteSecret);

	public function persistSettings();

	public function getSdk();

	public function nitroEvent($event, $integrationUrl, $store = null, $additional_meta_data = null);
    public function nitroGenerateWebhookToken($siteId);
}

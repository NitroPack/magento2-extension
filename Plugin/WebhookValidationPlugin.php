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
namespace NitroPack\NitroPack\Plugin;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\CsrfValidator;

use NitroPack\NitroPack\Api\NitroServiceInterface;

/**
 * Class WebhookValidationPlugin -
 * @package NitroPack\NitroPack\Plugin
 * @since 2.2.1
 * */
class WebhookValidationPlugin {

	protected $nitro = null;

	public function __construct(NitroServiceInterface $nitro) {
		$this->nitro = $nitro;
	}

	public function aroundValidate(CsrfValidator $subject, \Closure $proceed, RequestInterface $request, ActionInterface $action) {
		// @TODO check the nitro request header, signing, etc
		if ($this->nitro->isEnabled() && $request->getModuleName() == 'NitroPack') {
			return true;
		}

		return $proceed($request, $action);
	}

}

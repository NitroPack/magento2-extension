<?php
namespace NitroPack\NitroPack\Plugin;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\CsrfValidator;

use NitroPack\NitroPack\Api\NitroServiceInterface;

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
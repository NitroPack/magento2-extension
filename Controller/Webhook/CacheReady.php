<?php
namespace NitroPack\NitroPack\Controller\Webhook;

class CacheReady extends WebhookController {

	public function execute() {
		if ($url = $this->getRequest()->getParam('url', false)) {
			$this->nitro->reload(null, $url);
			$this->nitro->hasRemoteCache('', false);
		}
		return $this->textResponse('ok');
	}

}
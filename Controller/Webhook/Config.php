<?php

namespace NitroPack\NitroPack\Controller\Webhook;

class Config extends WebhookController
{

    public function execute()
    {
        $this->nitro->fetchConfig();
        return $this->textResponse('ok');
    }

}

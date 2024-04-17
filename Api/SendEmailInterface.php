<?php
/**
 * @author NitroPack Inc.
 * @copyright Copyright (c) NitroPack Inc. (https://www.nitropack.io)
 * @package NitroPack for Magento 2
 */

namespace NitroPack\NitroPack\Api;

use Magento\Framework\App\ResponseInterface;

interface SendEmailInterface
{

    /**
     * @param $toEmail
     * @param array $data
     * @return string|null
     */
    public function send($toEmail, array $data): ?string;

}

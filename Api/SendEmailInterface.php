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

use Magento\Framework\App\ResponseInterface;

/**
 * Interface SendEmailInterface
 * @api
 * @package NitroPack\NitroPack\Api
 * @since 3.0.0
 */
interface SendEmailInterface
{

    /**
     * @param $toEmail
     * @param array $data
     * @return string|null
     */
    public function send($toEmail, array $data): ?string;

}

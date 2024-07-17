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
namespace NitroPack\NitroPack\Logger;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Class Handler - NitroPack logger handler
 * @extends Base
 * @package NitroPack\NitroPack\Helper
 * @since 3.1.0
 */
class Handler extends Base
{
    /**
     * File Name
     * @var string
     */
    protected $fileName = '/var/log/nitropack.log';
}

<?php
/**
 * @author NitroPack Inc.
 * @copyright Copyright (c) NitroPack Inc. (https://www.nitropack.io)
 * @package NitroPack for Magento 2
 */

namespace NitroPack\NitroPack\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Class Handler
 * @package NitroPack\NitroPack\Logger
 */
class Handler extends Base
{
    /**
     * File Name
     * @var string
     */
    protected $fileName = '/var/log/nitropack.log';
}

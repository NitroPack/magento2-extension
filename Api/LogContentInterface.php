<?php
/**
 * @author NitroPack Inc.
 * @copyright Copyright (c) NitroPack Inc. (https://www.nitropack.io)
 * @package NitroPack for Magento 2
 */

namespace NitroPack\NitroPack\Api;

use Magento\Framework\App\ResponseInterface;

interface LogContentInterface
{
    public const NITROPACK_LOG_FILE_NAME = 'nitropack.log';
    public const NITROPACK_LOG_LINES_NUMBER = 500;

    /**
     * @return ResponseInterface
     */
    public function downloadLogFile(): ResponseInterface;

    /**
     * @param $isFile
     * @return string
     */
    public function getLogContent($isFile): string;

}

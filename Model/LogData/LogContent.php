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

namespace NitroPack\NitroPack\Model\LogData;

use Exception;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Response\Http\FileFactory;
use NitroPack\NitroPack\Api\LogContentInterface;
use Magento\Framework\Escaper;


/**
 * Class LogContent - Log Content Model
 * @implements LogContentInterface
 * @package NitroPack\NitroPack\Model\LogData
 * @since 3.0.0
 * */
class LogContent implements LogContentInterface
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    private $escaper;

    /**
     * @param DirectoryList $directoryList
     * @param File $fileDriver
     * @param FileFactory $fileFactory
     */
    public function __construct(
        DirectoryList $directoryList,
        File          $fileDriver,
        FileFactory   $fileFactory,
        Escaper       $escaper
    )
    {
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
        $this->fileFactory = $fileFactory;
        $this->escaper = $escaper;
    }

    /**
     * @throws Exception
     */
    public function downloadLogFile(): ResponseInterface
    {
        $downloadName = self::NITROPACK_LOG_FILE_NAME;
        $content['type'] = 'filename';
        $content['value'] = self::NITROPACK_LOG_FILE_NAME;
        $content['rm'] = 0;
        return $this->fileFactory->create($downloadName, $content, DirectoryList::LOG);
    }

    /**
     * @param $isFile
     * @return string
     */
    public function getLogContent($isFile = false): string
    {
        $eol = $isFile ? PHP_EOL : '<br>';
        $logContent = '';
        $log = $this->getLogFileContentArray();

        if ($log) {
            foreach ($log as $logLine) {
                $logContent .= $this->escaper->escapeHtml($logLine) . $eol;
            }
        }

        return $logContent;
    }

    /**
     * @return array|null
     */
    private function getLogFileContentArray(): ?array
    {
        $content = null;

        try {
            $path = $this->directoryList->getPath(DirectoryList::LOG);
            $logContent = $this->fileDriver->fileGetContents($path . DIRECTORY_SEPARATOR . self::NITROPACK_LOG_FILE_NAME);
            $content = array_slice(array_filter(explode(PHP_EOL, $logContent)), -self::NITROPACK_LOG_LINES_NUMBER);
        } catch (FileSystemException $e) {

        }

        return $content;
    }
}

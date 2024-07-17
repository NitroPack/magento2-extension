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
namespace NitroPack\NitroPack\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use NitroPack\NitroPack\Api\LogContentInterface;
use NitroPack\NitroPack\Logger\Logger;

/**
 * Class RecentLogRecords - Recent log records cron job
 * @package NitroPack\NitroPack\Cron
 * @since 3.0.0
 */
class RecentLogRecords
{

    /**
     * @var LogContentInterface
     */
    private $logContentInterface;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var File
     */
    private $file;

    private $logger;

    /**
     * @param LogContentInterface $logContentInterface
     * @param Filesystem $filesystem
     * @param File $file
     */
    public function __construct(
        LogContentInterface $logContentInterface,
        Filesystem $filesystem,
        File $file,
        Logger $logger
    )
    {
        $this->logContentInterface = $logContentInterface;
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $logContent = $this->logContentInterface->getLogContent(true);

        if (empty($logContent)) {
            return;
        }

        try {
            $directory = $this->filesystem->getDirectoryWrite(DirectoryList::LOG);
            $filepath = $directory->getAbsolutePath() . DIRECTORY_SEPARATOR . LogContentInterface::NITROPACK_LOG_FILE_NAME;
            if ($this->file->fileExists($filepath)){
                $this->file->rm($filepath);
            }
            $directory->writeFile($filepath, $logContent);
        } catch (FileSystemException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

}

<?php

namespace NitroPack\NitroPack\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use NitroPack\NitroPack\Api\LogContentInterface;
use NitroPack\NitroPack\Logger\Logger;

/**
 * class RecentLogRecords
 * @package NitroPack\NitroPack\Cron
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

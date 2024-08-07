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

declare(strict_types=1);

namespace NitroPack\NitroPack\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use NitroPack\NitroPack\Api\PurgeManagementInterface;
use NitroPack\NitroPack\Model\FullPageCache\PurgeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
/**
 * Class PurgeByCategoryId - Purge By Category Id Command to clear the NitroPack cache for specific categories
 * @extends Command
 * @package NitroPack\NitroPack\Block
 * @since 3.2.0
 */
class PurgeByCategoryId extends Command
{
    private const CATEGORY_IDS = 'categoryIds';

    /**
     * @var PurgeManagementInterface
     */
    private $purgeManagementInterface;

    /**
     * @var PurgeInterface
     */
    private $purgeInterface;

    private $state;

    /**
     * @param PurgeManagementInterface $purgeManagementInterface
     * @param PurgeInterface $purgeInterface
     */
    public function __construct(
        PurgeManagementInterface $purgeManagementInterface,
        PurgeInterface           $purgeInterface,
        State   $state
    )
    {
        $this->purgeManagementInterface = $purgeManagementInterface;
        $this->purgeInterface = $purgeInterface;
        $this->state = $state;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('nitropack:purge:category');
        $this->setDescription('Clears NitroPack cache for specific categories.');
        $this->addArgument(
            self::CATEGORY_IDS,
            InputArgument::IS_ARRAY | InputArgument::REQUIRED,
            'Category id'
        );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exitCode = 0;

        if ($categoryIds = $input->getArgument(self::CATEGORY_IDS)) {
            try {
                $this->state->setAreaCode(Area::AREA_FRONTEND);
                $this->purgeManagementInterface->purgeBycategoryIds($categoryIds);
                $output->writeln('<info>Successfully purged NitroPack cache for category IDs ' . implode(',', $categoryIds) . '</info>');
            } catch (\Exception $exception) {
                $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
                $exitCode = 1;
            }
        }

        return $exitCode;
    }
}

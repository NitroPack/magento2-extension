<?php

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

class PurgeByTags extends Command
{
    private const TAGS = 'tags';

    /**
     * @var PurgeManagementInterface
     */
    private $purgeManagementInterface;

    /**
     * @var PurgeInterface
     */
    private $purgeInterface;

    /**
     * @var State
     */
    private $state;

    /**
     * @param PurgeManagementInterface $purgeManagementInterface
     * @param PurgeInterface $purgeInterface
     * @param State $state
     */
    public function __construct(
        PurgeManagementInterface $purgeManagementInterface,
        PurgeInterface           $purgeInterface,
        State                    $state
    )
    {
        $this->purgeManagementInterface = $purgeManagementInterface;
        $this->purgeInterface = $purgeInterface;
        $this->state = $state;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('nitropack:purge:tag');
        $this->setDescription('Clears NitroPack cache for specific tags.');
        $this->addArgument(
            self::TAGS,
            InputArgument::IS_ARRAY | InputArgument::REQUIRED,
            'Cache tag'
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

        if ($tags = $input->getArgument(self::TAGS)) {
            try {
                $this->state->setAreaCode(Area::AREA_FRONTEND);
                $this->purgeManagementInterface->purgeByCacheTags($tags);
                $output->writeln('<info>Successfully purged NitroPack cache for the following tags: ' . implode(',', $tags) . '</info>');
            } catch (\Exception $exception) {
                $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
                $exitCode = 1;
            }
        }

        return $exitCode;
    }
}


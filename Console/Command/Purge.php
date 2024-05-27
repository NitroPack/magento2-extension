<?php

declare(strict_types=1);

namespace NitroPack\NitroPack\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use NitroPack\NitroPack\Api\PurgeManagementInterface;
use NitroPack\NitroPack\Model\FullPageCache\PurgeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Purge extends Command
{
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
        State $state
    )
    {
        $this->purgeManagementInterface = $purgeManagementInterface;
        $this->purgeInterface = $purgeInterface;
        $this->state = $state;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('nitropack:purge');
        $this->setDescription('Clears the NitroPack cache completely.');
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

        try {
            $this->state->setAreaCode(Area::AREA_FRONTEND);
            $this->purgeManagementInterface->purgeAll();
            $output->writeln('<info>Successfully purged NitroPack cache</info>');
        } catch (\Exception $exception) {
            $exitCode = 1;
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
        }

        return $exitCode;
    }
}

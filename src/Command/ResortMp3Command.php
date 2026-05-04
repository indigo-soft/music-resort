<?php

declare(strict_types=1);

namespace MusicResort\Command;

use JsonException;
use MusicResort\Helpers\ResortMp3Helper;
use MusicResort\Service\ConsoleCommandService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'music:resort',
    description: 'Sort MP3 files by artist into separate folders'
)]
final class ResortMp3Command extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @noinspection PhpUnused
     */
    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::REQUIRED, __('console.arg.source'))
            ->addArgument('destination', InputArgument::REQUIRED, __('console.arg.destination'))
            ->addOption('dry-run', null, InputOption::VALUE_NONE, __('console.opt.dry_run'))
            ->addOption('concurrency', 'c', InputOption::VALUE_REQUIRED, 'Number of parallel workers (default 1).', 1)
            ->addOption('worker-batch', null, InputOption::VALUE_REQUIRED, 'Internal: JSON file with list of files to process (used by parallel workers).')
            ->addOption('result-json', null, InputOption::VALUE_REQUIRED, 'Internal: File path to write JSON result (used by parallel workers).')
            ->setHelp(__('console.command.mp3_resort.help'));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $commandService = new ConsoleCommandService($input, $output);
        $sourceDir = $commandService->getSourceDir();
        $destinationDir = (string)$commandService->getDestinationDir();
        $dryRun = $commandService->isDryRun();

        return new ResortMp3Helper($io, $sourceDir, $destinationDir, $dryRun)->run(
            is_string($input->getOption('worker-batch')) ? $input->getOption('worker-batch') : null,
            is_string($input->getOption('result-json')) ? $input->getOption('result-json') : null,
            (int)$input->getOption('concurrency')
        );
    }
}

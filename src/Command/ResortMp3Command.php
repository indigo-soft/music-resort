<?php

declare(strict_types=1);

namespace MusicResort\Command;

use MusicResort\Service\ConsoleCommandService;
use MusicResort\Service\Mp3ResortService;
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

    /** @noinspection PhpUnused */
    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::REQUIRED, __('console.arg.source'))
            ->addArgument('destination', InputArgument::REQUIRED, __('console.arg.destination'))
            ->addOption('dry-run', null, InputOption::VALUE_NONE, __('console.opt.dry_run'))
            ->setHelp(__('console.command.mp3_resort.help'));
    }

    /** @noinspection PhpUnused */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $commandService = new ConsoleCommandService($input, $output);
        $sourceDir = $commandService->getSourceDir();
        $destinationDir = $commandService->getDestinationDir();
        $dryRun = $commandService->isDryRun();

        $service = new Mp3ResortService($sourceDir, $destinationDir, $io, $dryRun);
        $result = $service->resort();

        if ($result['status'] === Command::SUCCESS) {
            $io->success([
                __('console.success.resorted'),
                __('console.success.processed', ['processed' => $result['processed']]),
                __('console.success.errors', ['errors' => $result['errors']]),
            ]);
        }

        return $result['status'];
    }
}

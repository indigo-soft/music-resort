<?php

declare(strict_types=1);

namespace MusicResort\Command;

use MusicResort\Service\ConsoleCommandService;
use MusicResort\Service\EmptyDirsCleanupService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'music:clean-empty-dirs',
    description: 'Delete empty folders in the source folder'
)]
final class CleanEmptyDirsCommand extends Command
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
            ->addOption('dry-run', null, InputOption::VALUE_NONE, __('console.opt.dry_run'))
            ->setHelp(__('console.command.empty_dirs.help'));
    }

    /** @noinspection PhpUnused */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $commandService = new ConsoleCommandService($input, $output);
        $sourceDir = $commandService->getSourceDir();
        $dryRun = $commandService->isDryRun();

        $service = new EmptyDirsCleanupService($sourceDir, $io, $dryRun);
        $result = $service->clean();

        if ($result['status'] === Command::SUCCESS) {
            $io->success([
                __('console.success.removed_empty_dirs', ['count' => $result['removed']]),
                __('console.success.processed', ['processed' => $result['processed']]),
                __('console.success.errors', ['errors' => $result['errors']]),
            ]);
        }

        return $result['status'];
    }
}

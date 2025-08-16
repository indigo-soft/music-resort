<?php

declare(strict_types=1);

namespace Root\MusicLocal\Command;

use Root\MusicLocal\Component\ConsoleStyle;
use Root\MusicLocal\Service\ConfigService;
use Root\MusicLocal\Service\Mp3DeduplicateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mp3:deduplicate',
    description: 'Deduplicate audio files by artist and title'
)]
final class DeduplicateMp3Command extends Command
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
            ->setHelp(__('console.command.mp3_deduplicate.help'));
    }

    /** @noinspection PhpUnused */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new ConsoleStyle($input, $output);

        $sourceDir = (string)$input->getArgument('source');
        $dryRun = (bool)$input->getOption('dry-run');

        if (ConfigService::get('app.debug')) {
            $dryRun = true;
        }

        $service = new Mp3DeduplicateService($sourceDir, $io, $dryRun);
        $result = $service->deduplicate();

        if ($result['status'] === Command::SUCCESS) {
            $io->success([
                __('console.success.deduplicated'),
                __('console.success.processed', ['processed' => $result['processed']]),
                __('console.success.errors', ['errors' => $result['errors']]),
            ]);
        }

        return $result['status'];
    }
}

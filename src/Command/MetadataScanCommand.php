<?php

declare(strict_types=1);

namespace MusicResort\Command;

use MusicResort\Service\ConsoleCommandService;
use MusicResort\Service\MetadataScanService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'metadata:scan',
    description: 'Scan the music collection and store file metadata in the database',
)]
final class MetadataScanCommand extends Command
{
    public function __construct(
        private readonly MetadataScanService $scanService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::REQUIRED, __('console.arg.source'))
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, __('console.opt.scan_limit'))
            ->setHelp(__('console.command.metadata_scan.help'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandService = new ConsoleCommandService($input, $output);
        $sourceDir      = $commandService->getSourceDir();

        if (!is_dir($sourceDir)) {
            $output->writeln('<error>' . __('console.error.source_not_exists', ['path' => $sourceDir]) . '</error>');

            return Command::FAILURE;
        }

        $rawLimit = $input->getOption('limit');
        $limit    = $rawLimit !== null ? max(1, (int)$rawLimit) : null;

        $summary = $this->scanService->scan(
            sourceDir: $sourceDir,
            limit: $limit,
            onProgress: static function(string $event, string $filePath, array $context) use ($output): void {
                match ($event) {
                    'scanned' => $output->isVerbose()
                        ? $output->writeln('  ' . __('console.info.scan_file', ['file' => basename($filePath)]))
                        : null,
                    'unreadable' => $output->writeln(
                        '  <comment>' . __('console.warning.scan_unreadable', ['file' => basename($filePath)]) . '</comment>',
                    ),
                    default => null,
                };
            },
        );

        $output->writeln('');
        $output->writeln('<info>' . __('console.success.scan_done', [
            'total'      => $summary['total'],
            'scanned'    => $summary['scanned'],
            'unreadable' => $summary['unreadable'],
        ]) . '</info>');

        return $summary['scanned'] === 0 && $summary['total'] > 0
            ? Command::FAILURE
            : Command::SUCCESS;
    }
}

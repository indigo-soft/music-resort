<?php

declare(strict_types=1);

namespace MusicResort\Command;

use MusicResort\Database\Migration\DatabaseMigrationService;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'migrate:up',
    description: 'Apply pending database migrations',
)]
final class MigrateCommand extends Command
{
    public function __construct(
        private readonly DatabaseMigrationService $migrationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            'Applies any pending .sql migrations from db/migrations/ in order. '
            . 'Each migration runs inside a transaction — if it fails the transaction '
            . 'is rolled back and the command exits with an error.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1. Validate write access
        try {
            $this->migrationService->assertWritable();
        } catch (RuntimeException $e) {
            $output->writeln('<error>' . __('console.error.db_not_writable', ['error' => $e->getMessage()]) . '</error>');

            return Command::FAILURE;
        }

        // 2. Check pending migrations
        $pending = $this->migrationService->getPending();

        if ($pending === []) {
            $output->writeln('<info>' . __('console.success.migrate_none') . '</info>');

            return Command::SUCCESS;
        }

        // 3. Apply migrations
        try {
            $applied = $this->migrationService->migrate();
        } catch (RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }

        foreach ($applied as $filename) {
            $output->writeln('  ' . __('console.info.migrate_applied', ['filename' => $filename]));
        }

        $output->writeln('');
        $output->writeln('<info>' . __('console.success.migrate_done', ['count' => count($applied)]) . '</info>');

        return Command::SUCCESS;
    }
}

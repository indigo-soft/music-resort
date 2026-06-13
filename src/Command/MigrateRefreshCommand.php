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
    name: 'migrate:refresh',
    description: 'Drop all non-system tables and re-run all migrations from scratch',
)]
final class MigrateRefreshCommand extends Command
{
    /**
     * @param DatabaseMigrationService $migrationService
     * @param list<string> $preserveTables tables to keep during refresh
     */
    public function __construct(
        private readonly DatabaseMigrationService $migrationService,
        private readonly array $preserveTables,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            'Drops all tables except system and log tables (configured via '
            . 'MIGRATION_PRESERVE_TABLES in .env), clears the migrations record, '
            . 'then re-applies all migrations from db/migrations/ in order.',
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

        // 2. Show preserved tables
        $output->writeln(
            __('console.warning.migrate_refresh_preserved', ['tables' => implode(', ', $this->preserveTables)]),
        );
        $output->writeln('');

        // 3. Drop non-preserved tables
        try {
            $dropped = $this->migrationService->dropAllExcept($this->preserveTables);
        } catch (RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }

        $output->writeln(__('console.info.migrate_refresh_dropped', ['count' => $dropped]));
        $output->writeln('');

        // 4. Clear migrations log and re-apply
        $this->migrationService->clearMigrations();

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
        $output->writeln(
            '<info>' . __('console.success.migrate_refresh_done', ['count' => count($applied)]) . '</info>',
        );

        return Command::SUCCESS;
    }
}

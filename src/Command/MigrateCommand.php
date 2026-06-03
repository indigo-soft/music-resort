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
        private readonly string $dbPath,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            'Checks the database connection, then applies any pending .sql migrations '
            . 'from db/migrations/ in order. Each migration runs inside a transaction — '
            . 'if it fails the transaction is rolled back and the command exits with an error.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1. Validate DB_PATH is set
        if ($this->dbPath === '') {
            $output->writeln('<error>' . __('error.db_path_not_set') . '</error>');

            return Command::FAILURE;
        }

        // 2. Validate directory exists
        $dbDir = dirname($this->dbPath);

        if (!is_dir($dbDir)) {
            $output->writeln(
                '<error>' . __('error.db_dir_not_found', ['path' => $dbDir]) . '</error>',
            );

            return Command::FAILURE;
        }

        // 3. Validate write access
        try {
            $this->migrationService->assertWritable();
        } catch (RuntimeException $e) {
            $output->writeln('<error>' . __('error.db_not_writable', ['error' => $e->getMessage()]) . '</error>');

            return Command::FAILURE;
        }

        // 4. Check pending migrations
        $pending = $this->migrationService->getPending();

        if ($pending === []) {
            $output->writeln('<info>' . __('success.migrate_none') . '</info>');

            return Command::SUCCESS;
        }

        // 5. Apply migrations
        try {
            $applied = $this->migrationService->migrate();
        } catch (RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }

        foreach ($applied as $filename) {
            $output->writeln('  ' . __('info.migrate_applied', ['filename' => $filename]));
        }

        $output->writeln('');
        $output->writeln('<info>' . __('success.migrate_done', ['count' => count($applied)]) . '</info>');

        return Command::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace MusicResort\Command;

use Closure;
use MusicResort\Service\ConsoleCommandService;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'music:all',
    description: 'Run all music maintenance commands in sequence'
)]
final class RunAllCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::REQUIRED, __('console.arg.source'))
            ->addArgument('destination', InputArgument::OPTIONAL, __('console.arg.destination'))
            ->addOption('dry-run', null, InputOption::VALUE_NONE, __('console.opt.dry_run'))
            ->setHelp('Runs commands in order: music:resort (if destination provided), music:fix-extensions, music:deduplicate, music:clean, music:clean-empty-dirs.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $commandService = new ConsoleCommandService($input, $output);
        $sourceDir = $commandService->getSourceDir();
        $destinationDir = $commandService->getDestinationDir();
        $dryRun = $commandService->isDryRun();

        $application = $this->getApplication();
        if ($application === null) {
            $io->error('Console application is not available.');

            return Command::FAILURE;
        }

        /**
         * @throws ExceptionInterface
         */
        $run = $this->runConsoleCommand($application, $io, $dryRun, $output);

        // 1) music:resort (only if destination provided)
        $status = $this->runResortIfNeeded($destinationDir, $sourceDir, $run, $io);
        if ($status !== Command::SUCCESS) {
            return $status;
        }

        // 2..5) remaining simple steps
        foreach (['music:fix-extensions', 'music:deduplicate', 'music:clean', 'music:clean-empty-dirs'] as $step) {
            $status = $this->runStep($step, $sourceDir, $run);
            if ($status !== Command::SUCCESS) {
                return $status;
            }
        }

        $io->success('All steps have been completed successfully.');

        return Command::SUCCESS;
    }

    /**
     * @param Application $application
     * @param SymfonyStyle $io
     * @param bool $dryRun
     * @param OutputInterface $output
     * @return Closure
     */
    private function runConsoleCommand(
        Application     $application,
        SymfonyStyle    $io,
        bool            $dryRun,
        OutputInterface $output): Closure
    {
        return function (string $name, array $arguments) use (
            $application,
            $io,
            $dryRun,
            $output
        ): int {

            // Ensure the correct command is targeted
            $arguments = array_merge(['command' => $name], $arguments);
            if ($dryRun) {
                $arguments['--dry-run'] = true;
            }

            $io->section(sprintf('Running: %s', $name));
            $cmd = $application->find($name);

            return $cmd->run(new ArrayInput($arguments), $output);
        };
    }

    /**
     * Run the resort step only if a destination is provided; otherwise, print a note.
     * Returns Command::SUCCESS when either skipped or the command succeeded; otherwise, the failure code.
     *
     * @param string|null $destinationDir
     * @param string $sourceDir
     * @param Closure $run
     * @param SymfonyStyle $io
     * @return int
     */
    private function runResortIfNeeded(
        ?string      $destinationDir,
        string       $sourceDir,
        Closure      $run,
        SymfonyStyle $io): int
    {
        if ($destinationDir !== null) {
            return $run('music:resort', [
                'source' => $sourceDir,
                'destination' => $destinationDir,
            ]);
        }

        $io->note('Destination is not provided — skipping music:resort step.');
        return Command::SUCCESS;
    }

    /**
     * Run a simple step that only needs the source argument.
     *
     * @param string $commandName
     * @param string $sourceDir
     * @param Closure $run
     * @return int
     */
    private function runStep(
        string  $commandName,
        string  $sourceDir,
        Closure $run): int
    {
        return $run($commandName, [
            'source' => $sourceDir,
        ]);
    }
}

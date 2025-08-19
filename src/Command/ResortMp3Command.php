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
use Symfony\Component\Process\Process;

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
     * @noinspection PhpUnused
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $commandService = new ConsoleCommandService($input, $output);
        $sourceDir = $commandService->getSourceDir();
        $destinationDir = $commandService->getDestinationDir();
        $dryRun = $commandService->isDryRun();

        $workerBatch = $input->getOption('worker-batch');
        $resultJson = $input->getOption('result-json');

        // Worker mode: process a given batch list and optionally dump the result JSON
        if (is_string($workerBatch) && $workerBatch !== '') {
            $paths = [];
            if (is_file($workerBatch)) {
                $json = file_get_contents($workerBatch);
                $decoded = $json !== false ? json_decode($json, true) : null;
                if (is_array($decoded)) {
                    $paths = array_values(array_filter($decoded, static fn($p) => is_string($p) && $p !== ''));
                }
            }

            $service = new Mp3ResortService($sourceDir, (string)$destinationDir, $io, $dryRun);
            $result = $service->processFilesFromList($paths);

            if (is_string($resultJson) && $resultJson !== '') {
                @file_put_contents($resultJson, json_encode($result));
            }

            if ($result['status'] === Command::SUCCESS) {
                $io->success([
                    __('console.success.resorted'),
                    __('console.success.processed', ['processed' => $result['processed']]),
                    __('console.success.errors', ['errors' => $result['errors']]),
                ]);
            }

            return $result['status'];
        }

        // Coordinator mode
        $concurrency = (int)$input->getOption('concurrency');
        $service = new Mp3ResortService($sourceDir, (string)$destinationDir, $io, $dryRun);
        if ($concurrency < 2) {
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

        // Parallel execution
        $allPaths = $service->listFiles();
        $total = count($allPaths);
        if ($total === 0) {
            $io->warning(__('console.warning.nothing_found'));

            return Command::SUCCESS;
        }

        if ($concurrency > $total) {
            $concurrency = $total;
        }

        $chunkSize = (int)ceil($total / $concurrency);
        $batches = array_chunk($allPaths, $chunkSize);

        $consolePath = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console');
        if ($consolePath === false) {
            $io->error('Console entry point not found.');

            return Command::FAILURE;
        }

        $processes = [];
        $tempFiles = [];
        $results = [
            'processed' => 0,
            'errors' => 0,
        ];

        foreach ($batches as $batch) {
            if (count($batch) === 0) {
                continue;
            }
            $batchFile = tempnam(sys_get_temp_dir(), 'resort_batch_');
            $resultFile = tempnam(sys_get_temp_dir(), 'resort_result_');
            if ($batchFile === false || $resultFile === false) {
                $io->error('Failed to create temporary files for worker.');

                return Command::FAILURE;
            }
            // Ensure .json extension for clarity
            $batchJsonFile = $batchFile . '.json';
            rename($batchFile, $batchJsonFile);
            $batchFile = $batchJsonFile;

            file_put_contents($batchFile, json_encode($batch));

            $args = [
                PHP_BINARY,
                $consolePath,
                'music:resort',
                $sourceDir,
                (string)$destinationDir,
                '--worker-batch=' . $batchFile,
                '--result-json=' . $resultFile,
            ];
            if ($dryRun) {
                $args[] = '--dry-run';
            }

            $proc = new Process($args, dirname(__DIR__, 2));
            $proc->start();

            $processes[] = [$proc, $resultFile, $batchFile];
            $tempFiles[] = $resultFile;
            $tempFiles[] = $batchFile;
        }

        $overallStatus = Command::SUCCESS;
        foreach ($processes as [$proc, $resultFile, $batchFile]) {
            $exitCode = $proc->wait();
            if ($exitCode !== 0) {
                $overallStatus = Command::FAILURE;
            }
            if (is_file($resultFile)) {
                $json = file_get_contents($resultFile);
                $decoded = $json !== false ? json_decode($json, true) : null;
                if (is_array($decoded)) {
                    $results['processed'] += (int)($decoded['processed'] ?? 0);
                    $results['errors'] += (int)($decoded['errors'] ?? 0);
                }
            }
        }

        // Cleanup temp files
        foreach ($tempFiles as $tf) {
            @unlink($tf);
        }

        if ($overallStatus === Command::SUCCESS) {
            $io->success([
                __('console.success.resorted'),
                __('console.success.processed', ['processed' => $results['processed']]),
                __('console.success.errors', ['errors' => $results['errors']]),
            ]);
        }

        return $overallStatus;
    }
}

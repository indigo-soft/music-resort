<?php

declare(strict_types=1);

namespace MusicResort\Helpers;

use JsonException;
use MusicResort\Service\FileResortService;
use MusicResort\Service\Mp3ResortService;
use MusicResort\Service\MusicMetadataServiceFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

final class ResortMp3Helper
{
    private SymfonyStyle $io;
    private string $sourceDir;
    private string $destinationDir;
    private bool $dryRun;

    public function __construct(SymfonyStyle $io, string $sourceDir, string $destinationDir, bool $dryRun)
    {
        $this->io = $io;
        $this->sourceDir = $sourceDir;
        $this->destinationDir = $destinationDir;
        $this->dryRun = $dryRun;
    }

    /**
     * @param string|null $workerBatch
     * @param string|null $resultJson
     * @param int $concurrency
     * @return int
     * @throws JsonException
     */
    public function run(?string $workerBatch, ?string $resultJson, int $concurrency): int
    {
        if (is_string($workerBatch) && $workerBatch !== '') {
            return $this->runWorkerMode($workerBatch, $resultJson);
        }

        return $this->runCoordinatorMode($concurrency);
    }

    /**
     * @param string $workerBatch
     * @param string|null $resultJson
     * @return int
     * @throws JsonException
     */
    private function runWorkerMode(string $workerBatch, ?string $resultJson): int
    {
        $paths = $this->loadBatchPaths($workerBatch);
        $service = $this->createResortService();
        $result = $service->processFilesFromList($paths);

        if (is_string($resultJson) && $resultJson !== '') {
            @file_put_contents($resultJson, json_encode($result, JSON_THROW_ON_ERROR));
        }

        $this->showSuccess($result);

        return $result['status'];
    }

    /**
     * @param int $concurrency
     * @return int
     * @throws JsonException
     */
    private function runCoordinatorMode(int $concurrency): int
    {
        $service = $this->createResortService();

        if ($concurrency < 2) {
            $result = $service->resort();
            $this->showSuccess($result);

            return $result['status'];
        }

        return $this->runParallelMode($service, $concurrency);
    }

    /**
     * @param Mp3ResortService $service
     * @param int $concurrency
     * @return int
     * @throws JsonException
     */
    private function runParallelMode(Mp3ResortService $service, int $concurrency): int
    {
        $allPaths = $service->listFiles();
        $total = count($allPaths);
        if ($total === 0) {
            $this->io->warning(__('console.warning.nothing_found'));

            return Command::SUCCESS;
        }

        $batches = $this->buildBatches($allPaths, $concurrency, $total);
        $consolePath = $this->resolveConsolePath();
        if ($consolePath === null) {
            $this->io->error('Console entry point not found.');

            return Command::FAILURE;
        }

        $workerRuntime = $this->spawnWorkerProcesses($batches, $consolePath);
        if ($workerRuntime === null) {
            return Command::FAILURE;
        }

        [$overallStatus, $results] = $this->collectWorkerResults($workerRuntime['processes']);
        $this->cleanupTempFiles($workerRuntime['tempFiles']);
        $this->showParallelSuccess($overallStatus, $results);

        return $overallStatus;
    }

    /**
     * @param string[] $allPaths
     * @param int $concurrency
     * @param int $total
     * @return array<int, string[]>
     */
    private function buildBatches(array $allPaths, int $concurrency, int $total): array
    {
        if ($concurrency > $total) {
            $concurrency = $total;
        }

        $chunkSize = (int)ceil($total / $concurrency);

        return array_chunk($allPaths, $chunkSize);
    }

    /**
     * @return string|null
     */
    private function resolveConsolePath(): ?string
    {
        $consolePath = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console');

        return $consolePath === false ? null : $consolePath;
    }

    /**
     * @param array<int, string[]> $batches
     * @param string $consolePath
     * @return array{processes:array<int, array{Process, string}>, tempFiles:string[]}|null
     * @throws JsonException
     */
    private function spawnWorkerProcesses(array $batches, string $consolePath): ?array
    {
        $processes = [];
        $tempFiles = [];

        foreach ($batches as $batch) {
            if (count($batch) === 0) {
                continue;
            }

            $workerFiles = $this->createWorkerFiles($batch);
            if ($workerFiles === null) {
                $this->io->error('Failed to create temporary files for worker.');

                return null;
            }

            $process = new Process(
                $this->buildWorkerArguments($consolePath, $workerFiles['batchFile'], $workerFiles['resultFile']),
                dirname(__DIR__, 2)
            );
            $process->start();

            $processes[] = [$process, $workerFiles['resultFile']];
            $tempFiles[] = $workerFiles['resultFile'];
            $tempFiles[] = $workerFiles['batchFile'];
        }

        return ['processes' => $processes, 'tempFiles' => $tempFiles];
    }

    /**
     * @param string[] $batch
     * @return array{batchFile:string,resultFile:string}|null
     * @throws JsonException
     */
    private function createWorkerFiles(array $batch): ?array
    {
        $batchFile = tempnam(sys_get_temp_dir(), 'resort_batch_');
        $resultFile = tempnam(sys_get_temp_dir(), 'resort_result_');
        if ($batchFile === false || $resultFile === false) {
            return null;
        }

        $batchJsonFile = $batchFile . '.json';
        rename($batchFile, $batchJsonFile);
        $batchFile = $batchJsonFile;

        file_put_contents($batchFile, json_encode($batch, JSON_THROW_ON_ERROR));

        return ['batchFile' => $batchFile, 'resultFile' => $resultFile];
    }

    /**
     * @param string $consolePath
     * @param string $batchFile
     * @param string $resultFile
     * @return string[]
     */
    private function buildWorkerArguments(string $consolePath, string $batchFile, string $resultFile): array
    {
        $args = [
            PHP_BINARY,
            $consolePath,
            'music:resort',
            $this->sourceDir,
            $this->destinationDir,
            '--worker-batch=' . $batchFile,
            '--result-json=' . $resultFile,
        ];
        if ($this->dryRun) {
            $args[] = '--dry-run';
        }

        return $args;
    }

    /**
     * @param array<int, array{Process, string}> $processes
     * @return array{int, array{processed:int, errors:int}}
     * @throws JsonException
     */
    private function collectWorkerResults(array $processes): array
    {
        $overallStatus = Command::SUCCESS;
        $results = [
            'processed' => 0,
            'errors' => 0,
        ];

        foreach ($processes as [$process, $resultFile]) {
            if ($process->wait() !== 0) {
                $overallStatus = Command::FAILURE;
            }

            if (!is_file($resultFile)) {
                continue;
            }

            $json = file_get_contents($resultFile);
            $decoded = $json !== false ? json_decode($json, true, 512, JSON_THROW_ON_ERROR) : null;
            if (is_array($decoded)) {
                $results['processed'] += (int)($decoded['processed'] ?? 0);
                $results['errors'] += (int)($decoded['errors'] ?? 0);
            }
        }

        return [$overallStatus, $results];
    }

    /**
     * @param string[] $tempFiles
     * @return void
     */
    private function cleanupTempFiles(array $tempFiles): void
    {
        foreach ($tempFiles as $tempFile) {
            @unlink($tempFile);
        }
    }

    /**
     * @param int $overallStatus
     * @param array{processed:int, errors:int} $results
     * @return void
     */
    private function showParallelSuccess(int $overallStatus, array $results): void
    {
        if ($overallStatus !== Command::SUCCESS) {
            return;
        }

        $this->io->success([
            __('console.success.resorted'),
            __('console.success.processed', ['processed' => $results['processed']]),
            __('console.success.errors', ['errors' => $results['errors']]),
        ]);
    }

    /**
     * @param string $workerBatch
     * @return string[]
     * @throws JsonException
     */
    private function loadBatchPaths(string $workerBatch): array
    {
        if (!is_file($workerBatch)) {
            return [];
        }

        $json = file_get_contents($workerBatch);
        $decoded = $json !== false ? json_decode($json, true, 512, JSON_THROW_ON_ERROR) : null;
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, static fn($path) => is_string($path) && $path !== ''));
    }

    /**
     * @return Mp3ResortService
     */
    private function createResortService(): Mp3ResortService
    {
        return new Mp3ResortService(
            $this->sourceDir,
            $this->destinationDir,
            $this->io,
            $this->dryRun,
            new MusicMetadataServiceFactory(),
            new FileResortService($this->io, $this->destinationDir, $this->dryRun)
        );
    }

    /**
     * @param array{status:int, processed:int, errors:int} $result
     * @return void
     */
    private function showSuccess(array $result): void
    {
        if ($result['status'] !== Command::SUCCESS) {
            return;
        }

        $this->io->success([
            __('console.success.resorted'),
            __('console.success.processed', ['processed' => $result['processed']]),
            __('console.success.errors', ['errors' => $result['errors']]),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace MusicResort\Service;

use Exception;
use FilesystemIterator;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Throwable;

final class EmptyDirsCleanupService
{
    private string $sourceDir;
    private SymfonyStyle $io;
    private bool $dryRun;

    public function __construct(string $sourceDir, SymfonyStyle $io, bool $dryRun = false)
    {
        $this->sourceDir = $sourceDir;
        $this->io = $io;
        $this->dryRun = $dryRun;
    }

    /**
     * Remove empty directories in the provided folder (recursively, excluding the root itself)
     *
     * @return array{status:int, processed:int, errors:int, removed:int}
     */
    public function clean(): array
    {
        $this->announceDryRun();

        if (!$this->isSourceDirValid()) {
            $this->io->error(__('console.error.source_not_exists',
                ['path' => $this->sourceDir]));
            return $this->buildResult(Command::FAILURE, 0, 0, 0);
        }

        // Ініціалізуємо прогрес за кількістю тек
        $totalDirs = $this->countDirectories();
        $this->io->progressStart($totalDirs);

        [$processed, $errors, $removed] = $this->processDirectories();

        $this->io->progressFinish();

        return $this->buildResult(
            Command::SUCCESS,
            $processed,
            $errors,
            $removed);
    }

    /**
     * @return void
     */
    private function announceDryRun(): void
    {
        if ($this->dryRun) {
            $this->io->note(__('console.dry_run.note'));
        }
    }

    /**
     * @return bool
     */
    private function isSourceDirValid(): bool
    {
        return is_dir($this->sourceDir);
    }

    /**
     * @param int $status
     * @param int $processed
     * @param int $errors
     * @param int $removed
     * @return int[]
     */
    private function buildResult(
        int $status,
        int $processed,
        int $errors,
        int $removed): array
    {
        return [
            'status' => $status,
            'processed' => $processed,
            'errors' => $errors,
            'removed' => $removed,
        ];
    }

    /**
     * @return int
     */
    private function countDirectories(): int
    {
        $finder = $this->createBaseFinder();
        return iterator_count($finder->getIterator());
    }

    /**
     * @return Finder
     */
    private function createBaseFinder(): Finder
    {
        $finder = new Finder();
        $finder->directories()->in($this->sourceDir)->depth('>= 0');
        return $finder;
    }

    /**
     * Iterate and process all directories, aggregating results
     *
     * @return array{0:int,1:int,2:int} [processed, errors, removed]
     */
    private function processDirectories(): array
    {
        $processed = 0;
        $errors = 0;
        $removed = 0;

        $directories = $this->createFinderSortedByDepth();
        $filesystem = new Filesystem();

        foreach ($directories as $dir) {
            $this->io->progressAdvance();
            [$p, $e, $r] = $this->processSingleDirectory($dir, $filesystem);
            $processed += $p;
            $errors += $e;
            $removed += $r;
        }

        return [$processed, $errors, $removed];
    }

    /**
     * @return Finder
     */
    private function createFinderSortedByDepth(): Finder
    {
        $finder = $this->createBaseFinder();
        // Sort by depth descending to remove the deepest first
        $finder->sort(function ($a, $b) {
            $da = substr_count($a->getRelativePathname(), DIRECTORY_SEPARATOR);
            $db = substr_count($b->getRelativePathname(), DIRECTORY_SEPARATOR);
            return $db <=> $da; // deeper first
        });
        return $finder;
    }

    /**
     * Process a single directory entry
     *
     * @param SplFileInfo $dir
     * @param Filesystem $filesystem
     * @return array{0:int,1:int,2:int} [processed_inc, errors_inc, removed_inc]
     */
    private function processSingleDirectory(
        SplFileInfo $dir,
        Filesystem  $filesystem): array
    {
        $directoryPath = $this->resolvePath($dir);
        if ($directoryPath === null) {
            return [0, 1, 0];
        }

        $processed = 1;
        try {
            $removed = $this->removeIfEmpty($directoryPath, $filesystem) ? 1 : 0;
            return [$processed, 0, $removed];
        } catch (Throwable $e) {
            // Логуємо як попередження, не зупиняючи весь процес
            $this->io->warning(__('console.warning.file_skipped', [
                'file' => basename($directoryPath),
                'message' => $e->getMessage(),
            ]));
            return [$processed, 1, 0];
        }
    }

    /**
     * @param SplFileInfo $dir
     * @return string|null
     */
    private function resolvePath(SplFileInfo $dir): ?string
    {
        $path = $dir->getRealPath() ?: $dir->getPathname();
        return is_string($path) ? $path : null;
    }

    /**
     * @param string $path
     * @param Filesystem $filesystem
     * @return bool
     */
    private function removeIfEmpty(string $path, Filesystem $filesystem): bool
    {
        if (!$this->isDirEmpty($path)) {
            return false;
        }

        if ($this->dryRun) {
            $this->io->note(__('console.note.dry_dir_deleted', ['path' => $path]));
        } else {
            $filesystem->remove($path);
            $this->io->info(__('console.info.dir_deleted', ['path' => $path]));
        }

        return true;
    }

    /**
     * @param string $dirPath
     * @return bool
     */
    private function isDirEmpty(string $dirPath): bool
    {
        if (!is_dir($dirPath)) {
            return false;
        }

        // Quick check using DirectoryIterator skipping dots
        try {
            $it = new FilesystemIterator($dirPath, FilesystemIterator::SKIP_DOTS);
            return !$it->valid();
        } catch (Exception) {
            return false;
        }
    }
}

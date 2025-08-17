<?php

declare(strict_types=1);

namespace MusicResort\Service;

use Exception;
use MusicResort\Component\ConsoleStyle;
use MusicResort\Exception\MusicMetadataException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class Mp3ResortService
{
    private string $sourceDir;
    private string $destinationDir;
    private ConsoleStyle $io;
    private bool $dryRun;

    /**
     * @param string $sourceDir
     * @param string $destinationDir
     * @param ConsoleStyle $io
     * @param bool $dryRun
     */
    public function __construct(string $sourceDir, string $destinationDir, ConsoleStyle $io, bool $dryRun = false)
    {
        $this->sourceDir = $sourceDir;
        $this->destinationDir = $destinationDir;
        $this->io = $io;
        $this->dryRun = $dryRun;
    }

    /**
     * Performs sorting of MP3 files into directories.
     *
     * @return array{status:int, processed:int, errors:int}
     */
    public function resort(): array
    {
        $this->showDryRunNote();

        if (!$this->validateSourceDir()) {
            return [
                'status' => Command::FAILURE,
                'processed' => 0,
                'errors' => 0,
            ];
        }

        // Create the destination directory if it does not exist
        $this->ensureDestinationDirectory();

        $finder = $this->prepareFinder();

        $this->initializeProgress($finder);

        [$processedCount, $errorCount] = $this->processFiles($finder);

        $this->finalizeProgress();

        return $this->buildResult($processedCount, $errorCount);
    }

    /**
     * @return void
     */
    private function ensureDestinationDirectory(): void
    {
        $fs = new Filesystem();
        if (!$fs->exists($this->destinationDir)) {
            if (!$this->dryRun) {
                $fs->mkdir($this->destinationDir);
                $this->io->info(__('console.info.dir_created', ['path' => $this->destinationDir]));
            } else {
                $this->io->note(__('console.note.dir_created_dry', ['path' => $this->destinationDir]));
            }
        }
    }

    /**
     * @return void
     */
    private function showDryRunNote(): void
    {
        if ($this->dryRun) {
            $this->io->note(__('console.dry_run.note'));
        }
    }

    /**
     * @return bool
     */
    private function validateSourceDir(): bool
    {
        if (!is_dir($this->sourceDir)) {
            $this->io->error(__('console.error.source_not_exists', ['path' => $this->sourceDir]));
            return false;
        }
        return true;
    }

    /**
     * @return Finder
     */
    private function prepareFinder(): Finder
    {
        $finder = new Finder();
        $finder->files()->in($this->sourceDir)->name(['*.mp3', '*.flac', '*.m4a']);

        return $finder;
    }

    /**
     * @param Finder $finder
     * @return void
     */
    private function initializeProgress(Finder $finder): void
    {
        $this->io->title(__('console.title.resort'));
        $this->io->progressStart(iterator_count($finder));
    }

    /**
     * @return void
     */
    private function finalizeProgress(): void
    {
        $this->io->progressFinish();
    }

    /**
     * @param Finder $finder
     * @return array{0:int,1:int} [processedCount, errorCount]
     */
    private function processFiles(Finder $finder): array
    {
        $processedCount = 0;
        $errorCount = 0;

        foreach ($finder as $file) {
            try {
                $this->processSingleFile($file->getRealPath());
                $processedCount++;
            } catch (Exception $e) {
                $errorCount++;
                $this->io->warning(__('console.warning.file_skipped', ['file' => $file->getFilename(), 'message' => $e->getMessage()]));
            }

            $this->io->progressAdvance();
        }

        return [$processedCount, $errorCount];
    }

    /**
     * @param string $filePath
     * @return void
     * @throws MusicMetadataException|Exception
     */
    private function processSingleFile(string $filePath): void
    {
        $metaData = new MusicMetadataService($filePath);
        $artist = $this->extractFirstArtist($metaData->getArtist());
        $title = $metaData->getTitle();
        $fileService = new FileResortService($this->io,
            $this->destinationDir,
            $this->dryRun,
            $artist,
            $title);
        $fileService->moveToArtistFolder($filePath);
    }

    /**
     * @return array{status:int, processed:int, errors:int}
     */
    private function buildResult(int $processed, int $errors): array
    {
        return [
            'status' => Command::SUCCESS,
            'processed' => $processed,
            'errors' => $errors,
        ];
    }

    /**
     * @param string $artist
     * @return string
     */
    private function extractFirstArtist(string $artist): string
    {
        // Case-insensitive split by common separators and "feat/ft/featuring" variants
        // Keep the original casing for the returned substring.
        $pattern = '/\s*(?:;|,|\/|&|\s+feat\.?|\s+ft\.?|\s+featuring)\s*/i';
        $parts = preg_split($pattern, $artist, -1, PREG_SPLIT_NO_EMPTY);
        if (is_array($parts) && isset($parts[0])) {
            $first = trim($parts[0]);
            if ($first !== '') {
                return $first;
            }
        }
        return trim($artist);
    }
}

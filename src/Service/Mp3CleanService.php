<?php

declare(strict_types=1);

namespace MusicResort\Service;

use Exception;
use MusicResort\Component\ConsoleStyle;
use MusicResort\Exception\MusicMetadataException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class Mp3CleanService
{
    private const array SUPPORTED_EXTENSIONS = ['*.mp3', '*.flac', '*.m4a'];
    private const int MIN_SIZE_BYTES = 100 * 1024; // 100KB

    private string $sourceDir;
    private ConsoleStyle $io;
    private bool $dryRun;

    public function __construct(string $sourceDir, ConsoleStyle $io, bool $dryRun = false)
    {
        $this->sourceDir = $sourceDir;
        $this->io = $io;
        $this->dryRun = $dryRun;
    }

    /**
     * Clean invalid audio files in the source directory.
     * Deletes files smaller than 100KB and files that are likely corrupted/not intact.
     *
     * @return array{status:int, processed:int, errors:int}
     */
    public function clean(): array
    {
        if ($this->dryRun) {
            $this->io->note(__('console.dry_run.note'));
        }

        if ($validationError = $this->validateSourceDirOrFail()) {
            return $validationError;
        }

        $finder = $this->buildFinder();
        $totalFiles = iterator_count($finder);

        $this->io->title('Clean invalid audio files');
        $this->io->progressStart($totalFiles);

        $processedCount = 0;
        $errorCount = 0;
        $filesystem = new Filesystem();

        foreach ($finder as $file) {
            $this->processFile($file, $filesystem, $processedCount, $errorCount);
        }

        $this->io->progressFinish();

        return [
            'status' => Command::SUCCESS,
            'processed' => $processedCount,
            'errors' => $errorCount,
        ];
    }

    /**
     * @param SplFileInfo $file
     * @param Filesystem $filesystem
     * @param int $processed
     * @param int $errors
     * @return void
     */
    private function processFile(SplFileInfo $file, Filesystem $filesystem, int &$processed, int &$errors): void
    {
        $path = $file->getRealPath();

        try {
            if ($path === false) {
                throw new Exception('Invalid path: ' . $file->getFilename());
            }

            $processed++;

            if ($this->shouldDelete($file)) {
                $errors += $this->removeFile($path, $filesystem);
            }
        } catch (Exception $e) {
            $errors++;
            $this->warnFileSkipped($path !== false ? basename($path) : $file->getFilename(), $e->getMessage());
        } finally {
            $this->io->progressAdvance();
        }
    }

    /**
     * @param string $fileName
     * @param string $message
     * @return void
     */
    private function warnFileSkipped(string $fileName, string $message): void
    {
        $this->io->warning(__('console.warning.file_skipped', [
            'file' => $fileName,
            'message' => $message,
        ]));
    }

    /**
     * @return array|null
     */
    private function validateSourceDirOrFail(): ?array
    {
        if (!is_dir($this->sourceDir)) {
            $this->io->error(__(
                'console.error.source_not_exists',
                ['path' => $this->sourceDir]
            ));

            return ['status' => Command::FAILURE, 'processed' => 0, 'errors' => 0];
        }

        return null;
    }

    /**
     * @return Finder
     */
    private function buildFinder(): Finder
    {
        $finder = new Finder();
        $finder->files()->in($this->sourceDir)->name(self::SUPPORTED_EXTENSIONS);

        return $finder;
    }

    /**
     * @param SplFileInfo $file
     * @return bool
     */
    private function shouldDelete(SplFileInfo $file): bool
    {
        $path = $file->getRealPath();
        if ($path === false) {
            return false;
        }

        $size = is_file($path) ? (int)filesize($path) : 0;
        if ($size < self::MIN_SIZE_BYTES) {
            return true;
        }

        return !$this->isFileIntact($path);
    }

    /**
     * @param string $path
     * @return bool
     */
    private function isFileIntact(string $path): bool
    {
        try {
            $meta = new MusicMetadataService($path, true);

            // Basic sanity checks
            $playtime = $meta->getDuration();
            if ($playtime <= 0) {
                return false;
            }

            // If bitrate exists and equals 0 => suspicious
            $bitrate = $meta->getBitrate();
            if ($bitrate === 0) {
                return false;
            }

            return true;

        } catch (MusicMetadataException) {
            return false;
        }
    }

    /**
     * @param string $path
     * @param Filesystem $fs
     * @return int
     */
    private function removeFile(string $path, Filesystem $fs): int
    {
        $fileName = basename($path);

        // dry-run
        if ($this->dryRun) {
            $this->getNote($fileName);
            return Command::SUCCESS;
        }

        try {
            $fs->remove($path);
            $this->io->info(__(
                'console.info.deleted',
                ['file' => $fileName]
            ));

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->io->warning(__(
                'console.warning.file_skipped',
                ['file' => $fileName, 'message' => $e->getMessage()]
            ));

            return Command::FAILURE;
        }
    }

    /**
     * @param string $fileName
     * @return void
     */
    private function getNote(string $fileName): void
    {
        $this->io->note(__(
            'console.note.dry_deleted',
            ['file' => $fileName]
        ));
    }
}

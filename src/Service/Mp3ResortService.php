<?php

declare(strict_types=1);

namespace Root\MusicLocal\Service;

use Exception;
use getID3;
use Root\MusicLocal\Component\ConsoleStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class Mp3ResortService
{
    private string $sourceDir;
    private string $destinationDir;
    private ConsoleStyle $io;
    private bool $dryRun;

    public function __construct(string $sourceDir, string $destinationDir, ConsoleStyle $io, bool $dryRun = false)
    {
        $this->sourceDir = $sourceDir;
        $this->destinationDir = $destinationDir;
        $this->io = $io;
        $this->dryRun = $dryRun;
    }

    /**
     * Виконує сортування MP3-файлів відносно директорій.
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

        // Створити цільову директорію, якщо вона не існує
        $this->ensureDestinationDirectory();

        $finder = $this->prepareFinder();

        $this->initializeProgress($finder);

        [$processedCount, $errorCount] = $this->processFiles($finder);

        $this->finalizeProgress();

        return $this->buildResult($processedCount, $errorCount);
    }

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
     * @throws Exception
     */
    private function processSingleFile(string $filePath): void
    {
        $mp3Info = new getID3();
        $info = $mp3Info->analyze($filePath);

        $tags = $this->extractTags($info);
        $artist = $this->extractArtist($tags);
        $title = $this->extractTitle($tags);

        $fileService = new FileResortService($this->io, $this->destinationDir, $this->dryRun, $artist, $title);
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
     * @throws Exception
     */
    private function extractArtist(array $tags): string
    {
        // Try different tag fields for artist information
        $artistFields = ['artist', 'albumartist', 'band', 'performer'];

        foreach ($artistFields as $field) {
            if (!empty($tags[$field])) {
                $artist = $tags[$field];

                if (is_array($artist)) {
                    $artist = $artist[0];
                }

                return $this->extractFirstArtist($artist);
            }
        }

        throw new Exception(__('console.error.no_artist'));
    }

    /**
     * @param string $artist
     * @return string
     */
    private function extractFirstArtist(string $artist): string
    {
        // Handle string with multiple artists separated by common delimiters
        $separators = [';', ',', '/', '&', ' feat.', ' ft.', ' featuring', ' Featuring'];

        foreach ($separators as $separator) {
            if (str_contains($artist, $separator)) {
                $artists = explode($separator, $artist);
                return trim($artists[0]);
            }
        }

        return trim($artist);
    }

    /**
     * @throws Exception
     */
    private function extractTitle(array $tags): string
    {
        // Try different tag fields for artist information
        $titleFields = ['title'];

        foreach ($titleFields as $field) {
            if (!empty($tags[$field])) {
                $title = $tags[$field];

                return trim($title[0]);
            }
        }

        throw new Exception(__('console.error.no_title'));
    }


    /**
     * @param array $info
     * @return array
     * @throws Exception
     */
    private function extractTags(array $info): array
    {
        match (true) {
            !array_key_exists('tags', $info) => throw new Exception(__('console.error.no_tags')),
            array_key_exists('id3v2', $info['tags']) => $tags = $info['tags']['id3v2'], // mp3 new
            array_key_exists('id3v1', $info['tags']) => $tags = $info['tags']['id3v1'], // mp3 old
            array_key_exists('quicktime', $info['tags']) => $tags = $info['tags']['quicktime'], // m4a
            array_key_exists('vorbiscomment', $info['tags']) => $tags = $info['tags']['vorbiscomment'], // flac
            default => throw new Exception(__('console.error.no_id3')),
        };

        return $tags;
    }
}

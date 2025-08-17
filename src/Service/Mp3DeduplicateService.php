<?php

declare(strict_types=1);

namespace MusicResort\Service;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class Mp3DeduplicateService
{
    private const array SUPPORTED_EXTENSIONS = ['*.mp3', '*.flac', '*.m4a'];

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
     * Deduplicate audio files in the source directory.
     * @return array{status:int, processed:int, errors:int}
     */
    public function deduplicate(): array
    {
        if ($this->dryRun) {
            $this->io->note(__('console.dry_run.note'));
        }

        if ($fail = $this->validateSourceDirOrFail()) {
            return $fail;
        }

        [$processed, $errors] = $this->runFlow();

        return [
            'status' => Command::SUCCESS,
            'processed' => $processed,
            'errors' => $errors,
        ];
    }

    /** @return array{0:int,1:int} */
    private function runFlow(): array
    {
        $finder = $this->buildFinder();
        $this->io->title(__('console.title.deduplicate'));
        $this->startProgress($finder);

        [$files, $processed, $errors] = $this->collectFileMetas($finder);
        $groups = $this->groupByArtistTitle($files);
        $errors += $this->deleteDuplicates($groups);

        [$collisions, $normErrors] = $this->normalizeSuffixes();
        $errors += $normErrors;
        $this->reportCollisions($collisions);

        $this->io->progressFinish();
        return [$processed, $errors];
    }

    private function validateSourceDirOrFail(): ?array
    {
        if (!is_dir($this->sourceDir)) {
            $this->io->error(__('console.error.source_not_exists', ['path' => $this->sourceDir]));
            return ['status' => Command::FAILURE, 'processed' => 0, 'errors' => 0];
        }
        return null;
    }

    private function buildFinder(): Finder
    {
        $finder = new Finder();
        $finder->files()->in($this->sourceDir)->name(self::SUPPORTED_EXTENSIONS);
        return $finder;
    }

    private function startProgress(Finder $finder): void
    {
        $total = iterator_count($finder);
        $this->io->progressStart($total);
    }

    /** @return array{0:array<int,array>,1:int,2:int} */
    private function collectFileMetas(Finder $finder): array
    {
        $files = [];
        $processed = 0;
        $errors = 0;
        foreach ($finder as $file) {
            $this->handleFinderFile($file, $files, $processed, $errors);
        }
        return [$files, $processed, $errors];
    }

    /** @param array<int,array> $files */
    private function handleFinderFile(SplFileInfo $file, array &$files, int &$processed, int &$errors): void
    {
        $path = $file->getRealPath();
        try {
            if ($path === false) {
                throw new Exception('Invalid path');
            }
            $files[] = $this->analyzeFile($path);
            $processed++;
        } catch (Exception $e) {
            $errors++;
            $this->io->warning(__('console.warning.file_skipped', ['file' => $file->getFilename(), 'message' => $e->getMessage()]));
        }
        $this->io->progressAdvance();
    }

    /** @param array<int,array{artist:string,title:string}> $files */
    private function groupByArtistTitle(array $files): array
    {
        $groups = [];
        foreach ($files as $m) {
            $key = $this->normalizeKey($m['artist'] . ' - ' . $m['title']);
            $groups[$key] ??= [];
            $groups[$key][] = $m;
        }
        return $groups;
    }

    private function deleteDuplicates(array $groups): int
    {
        $errors = 0;
        $fs = new Filesystem();
        foreach ($groups as $items) {
            if (count($items) <= 1) {
                continue;
            }
            usort($items, [$this, 'compareItems']);
            $errors += $this->removeFiles(array_slice($items, 1), $fs);
        }
        return $errors;
    }

    /** @param array<int,array> $toDelete */
    private function removeFiles(array $toDelete, Filesystem $fs): int
    {
        $errors = 0;
        foreach ($toDelete as $del) {
            $fileName = basename($del['path']);
            if (!$this->dryRun) {
                try {
                    $fs->remove($del['path']);
                    $this->io->info(__('console.info.deleted', ['file' => $fileName]));
                } catch (Exception $e) {
                    $errors++;
                    $this->io->warning(__('console.warning.file_skipped', ['file' => $fileName, 'message' => $e->getMessage()]));
                }
            } else {
                $this->io->note(__('console.note.dry_deleted', ['file' => $fileName]));
            }
        }
        return $errors;
    }

    /** @param array $a @param array $b */
    private function compareItems(array $a, array $b): int
    {
        return [$b['duration'], $b['size'], $b['bitrate'], $a['index']] <=> [$a['duration'], $a['size'], $a['bitrate'], $b['index']];
    }

    /** @return array{0:array<int,array{0:string,1:string}>,1:int} */
    private function normalizeSuffixes(): array
    {
        $collisions = [];
        $errors = 0;
        $fs = new Filesystem();
        $finder = $this->buildFinder();
        foreach ($finder as $file) {
            $path = $file->getRealPath();
            if ($path === false) {
                continue;
            }
            $errors += $this->normalizeSingleFileSuffix($path, $fs, $collisions);
        }
        return [$collisions, $errors];
    }

    /** @param array<int,array{0:string,1:string}> $collisions */
    private function normalizeSingleFileSuffix(string $path, Filesystem $fs, array &$collisions): int
    {
        $info = $this->computeNormalizedPath($path);
        if ($info === null) {
            return 0;
        }
        [$base, $newBase, $newPath] = $info;
        if (is_file($newPath)) {
            $collisions[] = [$path, $newPath];
            return 0;
        }
        return $this->performRenameOrNote($base, $newBase, $path, $newPath, $fs);
    }

    /** @return array{0:string,1:string,2:string}|null */
    private function computeNormalizedPath(string $path): ?array
    {
        $dir = dirname($path);
        $base = basename($path);
        if (preg_match('/^(.*)_([0-9]+)(\.[^.]+)$/', $base, $m)) {
            $newBase = $m[1] . $m[3];
            $newPath = $dir . DIRECTORY_SEPARATOR . $newBase;
            return [$base, $newBase, $newPath];
        }
        return null;
    }

    private function performRenameOrNote(string $base, string $newBase, string $path, string $newPath, Filesystem $fs): int
    {
        if (!$this->dryRun) {
            try {
                $fs->rename($path, $newPath);
                $this->io->info(__('console.info.renamed', ['from' => $base, 'to' => $newBase]));
                return 0;
            } catch (Exception $e) {
                $this->io->warning(__('console.warning.file_skipped', ['file' => $base, 'message' => $e->getMessage()]));
                return 1;
            }
        }
        $this->io->note(__('console.note.dry_renamed', ['from' => $base, 'to' => $newBase]));
        return 0;
    }

    private function reportCollisions(array $collisions): void
    {
        if ($collisions === []) {
            return;
        }
        $this->io->warning(__('console.warning.normalize_collisions_found', ['count' => count($collisions)]));
        foreach ($collisions as [$from, $to]) {
            $this->io->warning(__('console.warning.normalize_collision', ['from' => basename($from), 'to' => basename($to)]));
        }
    }

    /**
     * @param string $filePath
     * @return array{path:string,size:int,duration:float,bitrate:int,artist:string,title:string,index:int}
     * @throws Exception
     */
    private function analyzeFile(string $filePath): array
    {
        $metaData = new MusicMetadataService($filePath, true);
        $artist = $metaData->getArtist();
        $title = $metaData->getTitle();
        $duration = $metaData->getDuration();
        $bitrate = $metaData->getBitrate();
        $size = is_file($filePath) ? (int)filesize($filePath) : 0;

        static $idx = 0;
        $idx++;
        return [
            'path' => $filePath,
            'size' => $size,
            'duration' => $duration,
            'bitrate' => $bitrate,
            'artist' => $artist,
            'title' => $title,
            'index' => $idx,
        ];
    }

    private function normalizeKey(string $s): string
    {
        $s = trim($s);
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;
        return strtolower($s);
    }

}

<?php

declare(strict_types=1);

namespace Root\MusicLocal\Service;

use Exception;
use Root\MusicLocal\Component\ConsoleStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class Mp3DeduplicateService
{
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
     * Deduplicate audio files in the source directory.
     * @return array{status:int, processed:int, errors:int}
     */
    public function deduplicate(): array
    {
        if ($this->dryRun) {
            $this->io->note(__('console.dry_run.note'));
        }

        if (!is_dir($this->sourceDir)) {
            $this->io->error(__('console.error.source_not_exists', ['path' => $this->sourceDir]));
            return [
                'status' => Command::FAILURE,
                'processed' => 0,
                'errors' => 0,
            ];
        }

        $finder = new Finder();
        $finder->files()->in($this->sourceDir)->name(['*.mp3', '*.flac', '*.m4a']);

        $this->io->title(__('console.title.deduplicate'));
        $total = iterator_count($finder);
        $this->io->progressStart($total);

        $processed = 0;
        $errors = 0;

        // Build file metadata list
        $files = [];
        foreach ($finder as $file) {
            $path = $file->getRealPath();
            try {
                $meta = $this->analyzeFile($path);
                $files[] = $meta;
                $processed++;
            } catch (Exception $e) {
                $errors++;
                $this->io->warning(__('console.warning.file_skipped', ['file' => $file->getFilename(), 'message' => $e->getMessage()]));
            }
            $this->io->progressAdvance();
        }

        // Group by key: artist and title
        $groups = [];
        foreach ($files as $m) {
            $key = $this->normalizeKey($m['artist'] . ' - ' . $m['title']);
            $groups[$key] ??= [];
            $groups[$key][] = $m;
        }

        // Compare and delete duplicates per group
        $fs = new Filesystem();
        foreach ($groups as $items) {
            if (count($items) <= 1) {
                continue;
            }
            // Sort by:
            //    - duration desc,
            //    - size desc,
            //    - bitrate desc,
            //    - original index asc
            // to keep first in ties
            usort($items, function (array $a, array $b): int {
                return [$b['duration'], $b['size'], $b['bitrate'], $a['index']] <=> [$a['duration'], $a['size'], $a['bitrate'], $b['index']];
            });

            $toDelete = array_slice($items, 1);

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
        }

        // After deletions, normalize suffix _N before extension
        $collisions = [];
        $finder2 = new Finder();
        $finder2->files()->in($this->sourceDir)->name(['*.mp3', '*.flac', '*.m4a']);
        foreach ($finder2 as $file) {
            $path = $file->getRealPath();
            $dir = dirname($path);
            $base = basename($path);
            // Match suffix _N (number) before extension
            if (preg_match('/^(.*)_([0-9]+)(\.[^.]+)$/', $base, $m)) {
                $newBase = $m[1] . $m[3];
                $newPath = $dir . DIRECTORY_SEPARATOR . $newBase;
                if (is_file($newPath)) {
                    // Collision - different params; list for warning
                    $collisions[] = [$path, $newPath];
                    continue;
                }
                if (!$this->dryRun) {
                    try {
                        $fs->rename($path, $newPath);
                        $this->io->info(__('console.info.renamed', ['from' => $base, 'to' => $newBase]));
                    } catch (Exception $e) {
                        $errors++;
                        $this->io->warning(__('console.warning.file_skipped', ['file' => $base, 'message' => $e->getMessage()]));
                    }
                } else {
                    $this->io->note(__('console.note.dry_renamed', ['from' => $base, 'to' => $newBase]));
                }
            }
        }

        if ($collisions !== []) {
            $this->io->warning(__('console.warning.normalize_collisions_found', ['count' => count($collisions)]));
            foreach ($collisions as [$from, $to]) {
                $this->io->warning(__('console.warning.normalize_collision', ['from' => basename($from), 'to' => basename($to)]));
            }
        }

        $this->io->progressFinish();

        return [
            'status' => Command::SUCCESS,
            'processed' => $processed,
            'errors' => $errors,
        ];
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

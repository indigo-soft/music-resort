<?php

declare(strict_types=1);

namespace MusicResort\Service;

use Exception;
use getID3;
use MusicResort\Component\ConsoleStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Throwable;

final class FixExtensionsService
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
     * Fix file extensions based on metadata
     *
     * @return array{status:int, processed:int, errors:int}
     */
    public function fixExtension(): array
    {
        $this->showDryRunNote();

        if (!$this->validateSourceDir()) {
            return [
                'status' => Command::FAILURE,
                'processed' => 0,
                'errors' => 0,
            ];
        }

        $finder = $this->prepareFinder();
        $this->initializeProgress($finder);
        [$processed, $errors] = $this->processFiles($finder);
        $this->finalizeProgress();

        return [
            'status' => Command::SUCCESS,
            'processed' => $processed,
            'errors' => $errors,
        ];
    }

    private function showDryRunNote(): void
    {
        if ($this->dryRun) {
            $this->io->note(__('console.dry_run.note'));
        }
    }

    private function validateSourceDir(): bool
    {
        if (!is_dir($this->sourceDir)) {
            $this->io->error(__('console.error.source_not_exists', ['path' => $this->sourceDir]));
            return false;
        }
        return true;
    }

    private function prepareFinder(): Finder
    {
        $finder = new Finder();
        return $finder->files()->in($this->sourceDir);
    }

    private function initializeProgress(Finder $finder): void
    {
        $this->io->progressStart(iterator_count($finder->getIterator()));
    }

    private function finalizeProgress(): void
    {
        $this->io->progressFinish();
    }

    /**
     * @return array{0:int,1:int}
     */
    private function processFiles(Finder $finder): array
    {
        $fs = new Filesystem();
        $analyzer = new getID3();
        $processed = 0;
        $errors = 0;

        foreach ($finder as $file) {
            $this->io->progressAdvance();
            [$p, $e] = $this->processSingleFile($file->getRealPath() ?: '', $fs, $analyzer);
            $processed += $p;
            $errors += $e;
        }

        return [$processed, $errors];
    }

    /**
     * @return array{0:int,1:int}
     */
    private function processSingleFile(string $path, Filesystem $fs, getID3 $analyzer): array
    {
        if ($path === '') {
            return [0, 1];
        }
        try {
            $info = $this->analyzePath($analyzer, $path);
            if ($info === null) {
                return [0, 1];
            }
            return $this->handleExtensionFix($path, $info, $fs);
        } catch (Throwable $e) {
            $this->io->warning(__('console.warning.file_skipped', ['file' => basename($path), 'message' => $e->getMessage()]));
            return [0, 1];
        }
    }

    /**
     * @param getID3 $analyzer
     * @param string $path
     * @return array|null
     */
    private function analyzePath(getID3 $analyzer, string $path): ?array
    {
        $info = $analyzer->analyze($path);
        $err = $info['error'] ?? null;
        $errText = is_array($err) ? implode('; ', $err) : (is_string($err) ? $err : null);
        if ($errText !== null) {
            $this->io->warning(__('console.warning.file_skipped', ['file' => basename($path), 'message' => $errText]));
            return null;
        }
        return $info;
    }

    /**
     * @return array{0:int,1:int}
     */
    private function handleExtensionFix(string $path, array $info, Filesystem $fs): array
    {
        $expected = $this->detectExpectedExtension($info);
        if (!$expected) {
            return [0, 0];
        }
        $current = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: '');
        if ($current === $expected) {
            return [0, 0];
        }
        $target = $this->buildTargetPath($path, $expected, $fs);
        return $this->dryRun
            ? $this->logDryRunRename($path, $target)
            : $this->performRename($fs, $path, $target);
    }

    /**
     * @return array{0:int,1:int}
     */
    private function performRename(Filesystem $fs, string $path, string $target): array
    {
        try {
            $fs->rename($path, $target);
            $this->io->info(__('console.info.renamed', ['from' => basename($path), 'to' => basename($target)]));
            return [1, 0];
        } catch (Exception $e) {
            $this->io->warning(__('console.warning.file_skipped', ['file' => basename($path), 'message' => $e->getMessage()]));
            return [0, 1];
        }
    }

    /**
     * @return array{0:int,1:int}
     */
    private function logDryRunRename(string $path, string $target): array
    {
        $this->io->note(__('console.note.dry_renamed', ['from' => basename($path), 'to' => basename($target)]));
        return [1, 0];
    }

    private function buildTargetPath(string $path, string $expectedExt, Filesystem $fs): string
    {
        $dir = dirname($path);
        $base = pathinfo($path, PATHINFO_FILENAME);
        $target = $dir . DIRECTORY_SEPARATOR . $base . '.' . $expectedExt;
        for ($i = 1; $fs->exists($target); $i++) {
            $target = $dir . DIRECTORY_SEPARATOR . $base . '_' . $i . '.' . $expectedExt;
        }
        return $target;
    }

    private function detectExpectedExtension(array $info): ?string
    {
        $raw = $info['id3v2']['dataformat'] ?? $info['audio']['dataformat'] ?? $info['fileformat'] ?? '';
        $format = is_string($raw) ? strtolower($raw) : '';
        if ($format === '') {
            return null;
        }
        $map = [
            'mp3' => 'mp3', 'mp2' => 'mp2', 'mp1' => 'mp1', 'flac' => 'flac',
            'wav' => 'wav', 'wma' => 'wma', 'aac' => 'm4a', 'mp4' => 'm4a',
            'quicktime' => 'm4a', 'vorbis' => 'ogg',
        ];
        return $map[$format] ?? null;
    }
}

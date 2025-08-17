<?php

declare(strict_types=1);

namespace MusicResort\Service;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 *  Filesystem Service
 */
final class FileResortService
{
    private const int MAX_PATH_LENGTH = 150;
    private Filesystem $filesystem;
    private SymfonyStyle $io;
    private string $destinationDir;
    private bool $dryRun;
    private string|int $artist;
    private string|int $title;

    public function __construct(
        SymfonyStyle $io,
        string       $destinationDir,
        bool         $dryRun,
        string|int   $artist,
        string|int   $title)
    {
        $this->filesystem = new Filesystem();
        $this->io = $io;
        $this->destinationDir = $destinationDir;
        $this->dryRun = $dryRun;
        $this->artist = $artist;
        $this->title = $title;
    }

    /**
     * @param string $filePath
     * @return void
     */
    public function moveToArtistFolder(string $filePath): void
    {
        $artistFolder = $this->sanitizeFolderName($this->artist);
        $artistPath = $this->ensureArtistPath($artistFolder);

        $fileName = $this->buildFileName($this->artist, $this->title);
        $destinationPath = $this->getUniqueDestinationPath($artistPath . DIRECTORY_SEPARATOR . $fileName);

        $this->performMoveOrDryRun($filePath, $destinationPath, $artistFolder, $fileName);
    }

    /**
     * @param string $artistFolder
     * @return string
     */
    private function ensureArtistPath(string $artistFolder): string
    {
        $artistPath = $this->destinationDir . DIRECTORY_SEPARATOR . $artistFolder;
        if (!$this->filesystem->exists($artistPath)) {
            if (!$this->dryRun) {
                $this->filesystem->mkdir($artistPath);
                $this->io->note(__('console.note.artist_folder_created', ['folder' => $artistFolder]));
            } else {
                $this->io->note(__('console.note.artist_folder_created_dry', ['folder' => $artistFolder]));
            }
        }

        return $artistPath;
    }

    /**
     * @param string|int $artist
     * @param string|int $title
     * @return string
     */
    private function buildFileName(string|int $artist, string|int $title): string
    {
        $raw = $artist . ' - ' . $title;
        return $this->sanitizeFileName($raw);
    }

    /**
     * @param string $filePath
     * @param string $destinationPath
     * @param string $artistFolder
     * @param string $fileName
     * @return void
     */
    private function performMoveOrDryRun(string $filePath, string $destinationPath, string $artistFolder, string $fileName): void
    {
        if (!$this->dryRun) {
            $this->filesystem->rename($filePath, $destinationPath);
            $this->io->info(__('console.info.moved', ['file' => $fileName, 'dest' => $artistFolder . DIRECTORY_SEPARATOR . basename($destinationPath)]));
        } else {
            $this->io->note(__('console.note.dry_moved', ['file' => $fileName, 'dest' => $artistFolder . DIRECTORY_SEPARATOR . basename($destinationPath)]));
        }
    }

    /**
     * @param string $originalDestination
     * @return string
     */
    private function getUniqueDestinationPath(string $originalDestination): string
    {
        // Compute the path components at once
        $pathInfo = pathinfo($originalDestination);
        $dir = $pathInfo['dirname'] ?? '';
        $filename = $pathInfo['filename'] ?? ($pathInfo['basename'] ?? 'file');
        $ext = isset($pathInfo['extension']) && $pathInfo['extension'] !== '' ? '.' . $pathInfo['extension'] : '';

        $destinationPath = $originalDestination;
        $counter = 1;

        // Unified existence check (no branching by dryRun)
        while ($this->filesystem->exists($destinationPath)) {
            $destinationPath = $dir . DIRECTORY_SEPARATOR . $filename . '_' . $counter . $ext;
            $counter++;
        }

        return $destinationPath;
    }

    /**
     * @param string|int $artist
     * @return string
     */
    private function sanitizeFolderName(string|int $artist): string
    {
        $invalid = ['<', '>', ':', '"', '|', '?', '*', '/', '\\'];
        $sanitized = str_replace($invalid, '', $artist);
        $sanitized = trim($sanitized, '. ');
        if (strlen($sanitized) > 100) {
            $sanitized = substr($sanitized, 0, 100);
        }

        return $sanitized ?: __('console.fallback.unknown_artist_folder');
    }

    /**
     * Sanitize file name (Windows/Linux/macOS without forbidden characters)
     */
    private function sanitizeFileName(string $name): string
    {
        // Forbidden characters for Windows + forward slash and backslash
        $invalid = ['<', '>', ':', '"', '|', '?', '*', '/', '\\'];

        // Replace forbidden characters with underscores
        $sanitized = str_replace($invalid, '_', $name);

        // Remove control characters and normalize spaces
        $sanitized = preg_replace('/[\x00-\x1F\x7F]+/u', '', $sanitized) ?? '';
        $sanitized = preg_replace('/\s+/', ' ', $sanitized) ?? '';
        $sanitized = trim($sanitized, " .");

        // Limit length (to avoid MAX_PATH issues on Windows with deep paths)
        if (strlen($sanitized) > self::MAX_PATH_LENGTH) {
            $sanitized = substr($sanitized, 0, self::MAX_PATH_LENGTH);
        }

        // Ensure we have at least some name
        return $sanitized !== '' ? $sanitized : __('console.fallback.unknown_file_name');
    }
}

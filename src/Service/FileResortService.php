<?php

declare(strict_types=1);

namespace Root\MusicLocal\Service;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 *  Fylesystem Service
 */
final class FileResortService
{
    private Filesystem $filesystem;
    private SymfonyStyle $io;
    private string $destinationDir;
    private bool $dryRun;
    private string $artist;
    private string $title;

    public function __construct(SymfonyStyle $io, string $destinationDir, bool $dryRun, string $artist, string $title)
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
     * @param string $artist
     * @param string $title
     * @return string
     */
    private function buildFileName(string $artist, string $title): string
    {
        $raw = $artist . ' - ' . $title . '.mp3';
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
        // Обчислюємо складник шляху один раз
        $pathInfo = pathinfo($originalDestination);
        $dir = $pathInfo['dirname'] ?? '';
        $filename = $pathInfo['filename'] ?? ($pathInfo['basename'] ?? 'file');
        $ext = isset($pathInfo['extension']) && $pathInfo['extension'] !== '' ? '.' . $pathInfo['extension'] : '';

        $destinationPath = $originalDestination;
        $counter = 1;

        // Уніфікована перевірка існування (без розгалуження по dryRun)
        while ($this->filesystem->exists($destinationPath)) {
            $destinationPath = $dir . DIRECTORY_SEPARATOR . $filename . '_' . $counter . $ext;
            $counter++;
        }

        return $destinationPath;
    }

    /**
     * @param string $name
     * @return string
     */
    private function sanitizeFolderName(string $name): string
    {
        $invalid = ['<', '>', ':', '"', '|', '?', '*', '/', '\\'];
        $sanitized = str_replace($invalid, '', $name);
        $sanitized = trim($sanitized, '. ');
        if (strlen($sanitized) > 100) {
            $sanitized = substr($sanitized, 0, 100);
        }
        return $sanitized ?: __('console.fallback.unknown_artist_folder');
    }

    /**
     * Санітизація імені файлу (Windows/Linux/MacOS без заборонених символів)
     */
    private function sanitizeFileName(string $name): string
    {
        // Заборонені символи для Windows + коса риска та зворотна риска
        $invalid = ['<', '>', ':', '"', '|', '?', '*', '/', '\\'];
        // Замінюємо заборонені на підкреслення
        $sanitized = str_replace($invalid, '_', $name);
        // Прибираємо ті що керують символами й нормалізуємо пробіли
        $sanitized = preg_replace('/[\x00-\x1F\x7F]+/u', '', $sanitized) ?? '';
        $sanitized = preg_replace('/\s+/', ' ', $sanitized) ?? '';
        $sanitized = trim($sanitized, " .");
        // Обмежуємо довжину (щоб не ризикувати з MAX_PATH у Windows при глибоких шляхах)
        if (strlen($sanitized) > 150) {
            $sanitized = substr($sanitized, 0, 150);
        }
        // Гарантуємо хоча б якесь ім'я
        return $sanitized !== '' ? $sanitized : __('console.fallback.unknown_file_name');
    }
}

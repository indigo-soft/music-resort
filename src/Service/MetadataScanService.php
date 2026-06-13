<?php

declare(strict_types=1);

namespace MusicResort\Service;

use MusicResort\Database\Repository\MusicFileMetadataRepository;
use MusicResort\Exception\MusicMetadataException;
use MusicResort\Logger\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Throwable;

/**
 * Builds the collection inventory in the music_file_metadata table.
 *
 * Walks the source directory (same mp3|flac|m4a mask as the resort pipeline),
 * reads each file's metadata via getID3 (MusicMetadataService, ADR-0003) and
 * upserts one row per file. Files getID3 cannot parse — or that lack the
 * required tags — are marked unreadable and the scan continues; a single bad
 * file never aborts the run.
 *
 * This table is the artist source for MetadataEnrichService (Last.fm), so a
 * scan must run before metadata:enrich.
 *
 * DI: all collaborators via constructor (ADR-0002). Instantiate only in
 * bin/console.
 */
final class MetadataScanService
{
    public function __construct(
        private readonly MusicFileMetadataRepository $metadataRepository,
        private readonly MusicMetadataServiceFactory $metadataFactory,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Run the scan.
     *
     * @param string $sourceDir
     * @param int|null $limit process at most N files (null = all)
     * @param callable(string $event, string $filePath, array<string, mixed> $context): void|null $onProgress
     *                                                                                                        optional progress callback; $event is one of: 'scanned', 'unreadable'
     * @param ?callable $onProgress
     * @return array{total: int, scanned: int, unreadable: int}
     */
    public function scan(string $sourceDir, ?int $limit = null, ?callable $onProgress = null): array
    {
        $finder = new Finder();
        $finder
            ->files()
            ->ignoreVCS(true)
            ->ignoreDotFiles(true)
            ->in($sourceDir)
            ->exclude(['@eaDir', '.AppleDouble', '.AppleDB'])
            ->name('/\.(mp3|flac|m4a)$/i');

        $summary = [
            'total'      => 0,
            'scanned'    => 0,
            'unreadable' => 0,
        ];

        $processed = 0;

        foreach ($finder as $file) {
            if ($limit !== null && $limit > 0 && $processed >= $limit) {
                break;
            }

            $path = $file->getRealPath();

            if ($path === false) {
                continue;
            }

            $processed++;
            $summary['total']++;

            try {
                $this->metadataRepository->upsert($this->buildRow($path));
                $summary['scanned']++;
                $this->report($onProgress, 'scanned', $path);
            } catch (MusicMetadataException $e) {
                $this->metadataRepository->markUnreadable($path);
                $summary['unreadable']++;
                $this->logger->warning('File could not be scanned', [
                    'file'  => $path,
                    'error' => $e->getMessage(),
                ]);
                $this->report($onProgress, 'unreadable', $path);
            } catch (Throwable $e) {
                // Any other failure (e.g. DB error) is logged and skipped so one
                // file never aborts the whole scan.
                $this->metadataRepository->markUnreadable($path);
                $summary['unreadable']++;
                $this->logger->error('Unexpected error during scan', [
                    'file'      => $path,
                    'exception' => $e::class,
                    'error'     => $e->getMessage(),
                ]);
                $this->report($onProgress, 'unreadable', $path);
            }
        }

        $this->logger->info('metadata:scan finished', $summary);

        return $summary;
    }

    /**
     * Assemble the music_file_metadata row from a file's metadata.
     *
     * @param string $path
     * @return array<string, mixed>
     */
    private function buildRow(string $path): array
    {
        $meta = $this->metadataFactory->createFor($path);

        $fileSize = @filesize($path);

        return [
            'file_path'    => $path,
            'format'       => $meta->getFormat(),
            'duration'     => $meta->getDuration(),
            'bitrate'      => $meta->getBitrate(),
            'file_size'    => $fileSize !== false ? $fileSize : null,
            'title'        => (string)$meta->getTitle(),
            'artist'       => (string)$meta->getArtist(),
            'album'        => $meta->getAlbum(),
            'album_artist' => $meta->getAlbumArtist(),
            'track_number' => $meta->getTrackNumber(),
            'year'         => $meta->getYear(),
            'genre'        => $meta->getGenre(),
            'comment'      => $meta->getComment(),
            'tag_source'   => $meta->getTagSource(),
        ];
    }

    /**
     * @param callable(string, string, array<string, mixed>): void|null $onProgress
     * @param string $event
     * @param string $filePath
     * @param array<string, mixed> $context
     */
    private function report(?callable $onProgress, string $event, string $filePath, array $context = []): void
    {
        if ($onProgress !== null) {
            $onProgress($event, $filePath, $context);
        }
    }
}

<?php

declare(strict_types=1);

namespace MusicResort\Service;

use getID3;
use MusicResort\Exception\MusicMetadataException;

final class MusicMetadataService
{
    private const array EXTENSIONS_MAPPING = [
        'mp3' => 'mp3',
        'mp2' => 'mp2',
        'mp1' => 'mp1',
        'flac' => 'flac',
        'wav' => 'wav',
        'wma' => 'wma',
        'vorbis' => 'ogg',
        'aac' => 'm4a',
        'mp4' => 'm4a',
        'quicktime' => 'm4a',
    ];

    public readonly ?int $bitrate;
    private readonly array $metaData;
    private readonly array $tags;
    private readonly ?string $tagSource;
    private readonly int|string|null $artist;
    private readonly int|string|null $title;
    private readonly ?int $duration;
    private readonly ?string $format;
    private ?string $genre;

    public function __construct(
        public string $filePath,
        public bool $isExtented = true
    ) {
        $this->metaData = $this->setMetadata($filePath);
        $this->tagSource = $this->detectTagSource();
        $this->tags = $this->extractTags();
        $this->artist = $this->extractArtist();
        $this->title = $this->extractTitle();
        $this->genre = $this->extractGenre();

        if ($isExtented) {
            $this->duration = $this->extractDuration();
            $this->bitrate = $this->extractBitrate();
            $this->format = $this->extactFormat();
        } else {
            $this->duration = null;
            $this->bitrate = null;
            $this->format = null;
        }
    }

    public function getArtist(): int|string
    {
        return $this->artist;
    }

    public function getTitle(): int|string
    {
        return $this->title;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function getBitrate(): ?int
    {
        return $this->bitrate;
    }

    public function getFormat(): ?string
    {
        return strtolower($this->format);
    }

    public function getGenre(): ?string
    {
        return ucfirst($this->genre);
    }

    public function getAlbum(): ?string
    {
        $value = $this->proccessTags(['album'], $this->tags);

        return $value !== null ? (string)$value : null;
    }

    public function getAlbumArtist(): ?string
    {
        $value = $this->proccessTags(['albumartist', 'album_artist', 'band'], $this->tags);

        return $value !== null ? (string)$value : null;
    }

    /**
     * Track number is kept as a string to preserve "03/12" notation.
     */
    public function getTrackNumber(): ?string
    {
        $value = $this->proccessTags(['track_number', 'tracknumber', 'track'], $this->tags);

        return $value !== null ? (string)$value : null;
    }

    public function getYear(): ?int
    {
        $value = $this->proccessTags(['year', 'date', 'creation_date'], $this->tags);

        if ($value === null) {
            return null;
        }

        // Extract a 4-digit year from values like "2019", "2019-04-01" or "01/04/2019".
        if (preg_match('/\d{4}/', (string)$value, $matches) === 1) {
            return (int)$matches[0];
        }

        return null;
    }

    public function getComment(): ?string
    {
        $value = $this->proccessTags(['comment', 'comments'], $this->tags);

        return $value !== null ? (string)$value : null;
    }

    /**
     * Which getID3 tag layer the tags were read from (id3v2/id3v1/quicktime/vorbiscomment),
     * or null when the file has no recognised tag layer.
     */
    public function getTagSource(): ?string
    {
        return $this->tagSource;
    }

    public function getCorrectExtension(): string
    {
        if (!array_key_exists((string)$this->getFormat(), self::EXTENSIONS_MAPPING)) {
            throw new MusicMetadataException('Unknown format!: ' . $this->format);
        }

        return self::EXTENSIONS_MAPPING[$this->format];
    }

    /**
     * @param string $filePath
     * @return array
     */
    private function setMetadata(string $filePath): array
    {
        $metaData = new getID3()->analyze($filePath);

        // Handle getID3 reported errors/warnings explicitly
        if (isset($metaData['error']) && $metaData['error']) {
            $errors = is_array($metaData['error']) ? implode('; ', $metaData['error']) : (string)$metaData['error'];

            throw new MusicMetadataException($errors);
        }
        if (isset($metaData['warning']) && $metaData['warning']) {
            $warnings = is_array($metaData['warning']) ? implode('; ', $metaData['warning']) : (string)$metaData['warning'];

            // Treat warnings as non-fatal? For reliability, we escalate to exception so a file is skipped with reason
            throw new MusicMetadataException($warnings);
        }

        return $metaData;
    }

    /**
     * Detect which getID3 tag layer is present, following the ADR-0003 priority
     * (id3v2 -> id3v1 -> quicktime -> vorbiscomment). Returns null when no
     * recognised tag layer exists. Read-only side-channel for inventory; does
     * not throw, unlike extractTags().
     *
     * @return string|null
     */
    private function detectTagSource(): ?string
    {
        if (!array_key_exists('tags', $this->metaData) || !is_array($this->metaData['tags'])) {
            return null;
        }

        return match (true) {
            array_key_exists('id3v2', $this->metaData['tags']) => 'id3v2',
            array_key_exists('id3v1', $this->metaData['tags']) => 'id3v1',
            array_key_exists('quicktime', $this->metaData['tags']) => 'quicktime',
            array_key_exists('vorbiscomment', $this->metaData['tags']) => 'vorbiscomment',
            default => null,
        };
    }

    /**
     * @return array
     */
    private function extractTags(): array
    {
        if (!array_key_exists('tags', $this->metaData)) {
            throw new MusicMetadataException(__('console.error.no_tags'));
        }

        return match (true) {
            array_key_exists('id3v2', $this->metaData['tags']) => $this->metaData['tags']['id3v2'], // mp3 new
            array_key_exists('id3v1', $this->metaData['tags']) => $this->metaData['tags']['id3v1'], // mp3 old
            array_key_exists('quicktime', $this->metaData['tags']) => $this->metaData['tags']['quicktime'], // m4a
            array_key_exists('vorbiscomment', $this->metaData['tags']) => $this->metaData['tags']['vorbiscomment'], // flac
            default => throw new MusicMetadataException(__('console.error.no_id3')),
        };
    }

    /**
     * @return string
     */
    private function extractArtist(): string
    {
        return $this->proccessTags(
            ['artist', 'albumartist', 'band', 'performer'],
            $this->tags,
            __('console.error.no_artist')
        );
    }

    /**
     * @return string
     */
    private function extractGenre(): string
    {
        return $this->proccessTags(
            ['genre'],
            $this->tags,
            __('console.error.no_genre')
        );
    }

    /**
     * @param array $fields
     * @param array $tags
     * @param string|null $exceptionMessage
     * @return string|int|null
     */
    private function proccessTags(array $fields, array $tags, ?string $exceptionMessage = null): int|string|null
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $tags) && !empty($tags[$field])) {
                $tag = $tags[$field];

                if (is_array($tag)) {
                    $tag = $tag[0] ?? null;
                }

                // int|float
                if (is_int($tag) || is_float($tag) || is_numeric($tag)) {
                    $tag = (int)$tag;

                    return $tag > 0 ? $tag : null;
                }

                // string
                if (is_string($tag)) {
                    $tag = mb_trim($tag);

                    return $tag !== '' ? $tag : null;
                }
            }
        }

        if ($exceptionMessage !== null) {
            throw new MusicMetadataException($exceptionMessage);
        }

        return null;
    }

    /**
     * @return string|int
     */
    private function extractTitle(): int|string
    {
        return $this->proccessTags(['title'], $this->tags, __('console.error.no_title'));
    }

    /**
     * @return int|null
     */
    private function extractDuration(): ?int
    {
        //fields: tags=>length, id3v2=>TLEN=>data, id3v2=>comments=>length,

        // tags=>length
        $durationFromTags = $this->proccessTags(['length'], $this->tags);
        if ($durationFromTags !== null) {
            return $durationFromTags;
        }

        // id3v2=>TLEN=>data
        if (array_key_exists('id3v2', $this->metaData)
            && array_key_exists('TLEN', $this->metaData['id3v2'])) {
            $durationFromMeta = $this->proccessTags(['data'], $this->metaData['id3v2']['TLEN']);
            if ($durationFromMeta !== null) {
                return $durationFromMeta;
            }
        }

        return null;
    }

    /**
     * @return int|null
     */
    private function extractBitrate(): ?int
    {
        // fields: audio=>bitrate,
        return $this->proccessTags(['bitrate'], $this->metaData);
    }

    private function extactFormat(): ?string
    {
        // fields: audio=>dataformat, fileformat

        // audio=>dataformat
        if (array_key_exists('audio', $this->metaData)
            && array_key_exists('dataformat', $this->metaData['audio'])) {
            $formatFromAudio = $this->proccessTags(['dataformat'], $this->metaData['audio']);
            if ($formatFromAudio !== null) {
                return $formatFromAudio;
            }
        }

        // fileformat
        if (array_key_exists('fileformat', $this->metaData)) {
            $durationFromMeta = $this->proccessTags(['fileformat'], $this->metaData);
            if ($durationFromMeta !== null) {
                return $durationFromMeta;
            }
        }

        return null;
    }
}

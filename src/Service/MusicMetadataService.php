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
    private readonly string|int $artist;
    private readonly string|int $title;
    private readonly ?int $duration;
    private readonly ?string $format;

    public function __construct(
        public string $filePath,
        public bool   $isExtented = true)
    {
        $this->metaData = $this->setMetadata($filePath);
        $this->tags = $this->extractTags();
        $this->artist = $this->extractArtist();
        $this->title = $this->extractTitle();

        if ($isExtented) {
            $this->duration = $this->extractDuration();
            $this->bitrate = $this->extractBitrate();
            $this->format = $this->extactFormat();
        }
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
     * @return array
     */
    private function extractTags(): array
    {
        match (true) {
            !array_key_exists('tags', $this->metaData) => throw new MusicMetadataException(__('console.error.no_tags')),
            array_key_exists('id3v2', $this->metaData['tags']) => $tags = $this->metaData['tags']['id3v2'], // mp3 new
            array_key_exists('id3v1', $this->metaData['tags']) => $tags = $this->metaData['tags']['id3v1'], // mp3 old
            array_key_exists('quicktime', $this->metaData['tags']) => $tags = $this->metaData['tags']['quicktime'], // m4a
            array_key_exists('vorbiscomment', $this->metaData['tags']) => $tags = $this->metaData['tags']['vorbiscomment'], // flac
            default => throw new MusicMetadataException(__('console.error.no_id3')),
        };

        return $tags;
    }

    /**
     * @return string
     */
    private function extractArtist(): string
    {
        return $this->proccessTags(
            ['artist', 'albumartist', 'band', 'performer'],
            $this->tags,
            __('console.error.no_artist'));
    }

    /**
     * @param array $fields
     * @param array $tags
     * @param string|null $exceptionMessage
     * @return string|int|null
     */
    private function proccessTags(array $fields, array $tags, ?string $exceptionMessage = null): string|int|null
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
    private function extractTitle(): string|int
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
        if (array_key_exists('id3v2', $this->metaData) &&
            array_key_exists('TLEN', $this->metaData['id3v2'])) {
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
        if (array_key_exists('audio', $this->metaData) &&
            array_key_exists('dataformat', $this->metaData['audio'])) {
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

    public function getArtist(): string|int
    {
        return $this->artist;
    }

    public function getTitle(): string|int
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

    public function getCorrectExtension(): string
    {
        if (!in_array((string)$this->getFormat(), array_keys(self::EXTENSIONS_MAPPING), true)) {
            throw new MusicMetadataException('Unknown format!: ' . $this->format);
        }

        return self::EXTENSIONS_MAPPING[$this->format];
    }
}

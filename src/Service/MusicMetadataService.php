<?php

declare(strict_types=1);

namespace Root\MusicLocal\Service;

use getID3;
use Root\MusicLocal\Exception\MusicMetadataException;

class MusicMetadataService
{
    public function __construct()
    {

    }

    /**
     * @param string $filePath
     * @return array
     */
    public function getMetadata(string $filePath): array
    {
        $info = new getID3()->analyze($filePath);

        // Handle getID3 reported errors/warnings explicitly
        if (isset($info['error']) && $info['error']) {
            $errors = is_array($info['error']) ? implode('; ', $info['error']) : (string)$info['error'];
            throw new MusicMetadataException($errors);
        }
        if (isset($info['warning']) && $info['warning']) {
            $warnings = is_array($info['warning']) ? implode('; ', $info['warning']) : (string)$info['warning'];
            // Treat warnings as non-fatal? For reliability, we escalate to exception so a file is skipped with reason
            throw new MusicMetadataException($warnings);
        }

        return $info;
    }


    /**
     * @param array $info
     * @return array
     * @throws MusicMetadataException
     */
    public function extractTags(array $info): array
    {
        match (true) {
            !array_key_exists('tags', $info) => throw new MusicMetadataException(__('console.error.no_tags')),
            array_key_exists('id3v2', $info['tags']) => $tags = $info['tags']['id3v2'], // mp3 new
            array_key_exists('id3v1', $info['tags']) => $tags = $info['tags']['id3v1'], // mp3 old
            array_key_exists('quicktime', $info['tags']) => $tags = $info['tags']['quicktime'], // m4a
            array_key_exists('vorbiscomment', $info['tags']) => $tags = $info['tags']['vorbiscomment'], // flac
            default => throw new MusicMetadataException(__('console.error.no_id3')),
        };

        return $tags;
    }

    /**
     * @param array $tags
     * @return string
     * @throws MusicMetadataException
     */
    public function extractArtist(array $tags): string
    {
        // Try different tag fields for artist information
        $artistFields = ['artist', 'albumartist', 'band', 'performer'];

        foreach ($artistFields as $field) {
            if (!empty($tags[$field])) {
                $artist = $tags[$field];

                if (is_array($artist)) {
                    $artist = $artist[0];
                }

                return $artist;
            }
        }

        throw new MusicMetadataException(__('console.error.no_artist'));
    }

    /**
     * @param array $tags
     * @return string
     * @throws MusicMetadataException
     */
    public function extractTitle(array $tags): string
    {
        // Try different tag fields for title information (can extend if needed)
        $titleFields = ['title'];

        foreach ($titleFields as $field) {
            if (!empty($tags[$field])) {
                $title = $tags[$field];

                if (is_array($title)) {
                    $title = $title[0] ?? '';
                }

                if (!is_string($title)) {
                    break; // fall through to the generic error below
                }

                $title = trim($title);
                if ($title === '') {
                    break; // fall through
                }

                return $title;
            }
        }

        throw new MusicMetadataException(__('console.error.no_title'));
    }
}

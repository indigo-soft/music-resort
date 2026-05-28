<?php

declare(strict_types=1);

namespace MusicResort\Enum;

enum AudioProcessingOperation: string
{
    /**
     * Remove silence from start and end of track.
     */
    case Trim = 'trim';

    /**
     * EBU R128 two-pass loudness normalization to -14 LUFS.
     */
    case Normalize = 'normalize';

    /**
     * Transcode non-MP3 format to MP3 via libmp3lame.
     */
    case Transcode = 'transcode';

    /**
     * All three operations applied in sequence: trim → normalize → transcode.
     */
    case Full = 'full';
}

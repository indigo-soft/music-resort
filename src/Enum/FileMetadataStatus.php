<?php

declare(strict_types=1);

namespace MusicResort\Enum;

enum FileMetadataStatus: string
{
    /**
     * File is present on disk and metadata is current.
     */
    case Active = 'active';

    /**
     * File no longer found at the recorded path (moved, deleted, renamed).
     */
    case Missing = 'missing';

    /**
     * getID3 could not read the file (corrupt, unreadable format, etc.).
     */
    case Unreadable = 'unreadable';
}

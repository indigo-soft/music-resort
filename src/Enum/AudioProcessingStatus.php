<?php

declare(strict_types=1);

namespace MusicResort\Enum;

enum AudioProcessingStatus: string
{
    /**
     * Recorded, not yet processed.
     */
    case Pending = 'pending';

    /**
     * Successfully processed by audio:process.
     */
    case Processed = 'processed';

    /**
     * Processing failed — see error_message column.
     */
    case Failed = 'failed';

    /**
     * Intentionally skipped (already MP3, no silence detected, etc.).
     */
    case Skipped = 'skipped';

    /**
     * Manually verified — ready for audio:cleanup-originals.
     */
    case Verified = 'verified';

    /**
     * Original removed by audio:cleanup-originals.
     */
    case Cleaned = 'cleaned';
}

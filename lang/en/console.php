<?php

return [
    'command' => [
        'mp3_resort' => [
            'description' => 'Sort MP3 files by artist into separate folders',
            'help' => 'This command sorts MP3 files by artist from a source directory to a destination directory. Use --dry-run to simulate without changes.',
        ],
        'mp3_deduplicate' => [
            'description' => 'Deduplicate audio files by artist and title',
            'help' => 'Scans files, compares by Artist+Title, deletes worse duplicates (shorter duration, smaller size, lower bitrate). Then removes _N suffixes; warns on name collisions. Use --dry-run to simulate.',
        ],
    ],

    'arg' => [
        'source' => 'Source directory with MP3 files',
        'destination' => 'Destination directory for sorted files',
    ],

    'opt' => [
        'dry_run' => 'Simulate without making filesystem changes',
    ],

    'dry_run' => [
        'note' => 'DRY-RUN MODE: No filesystem changes will be made',
    ],

    'error' => [
        'source_not_exists' => 'Source directory does not exist: :path',
        'no_artist' => 'No artist information found in metadata',
        'no_title' => 'No title information found in metadata',
        'no_tags' => 'No tags found in metadata',
        'no_id3' => 'No id3v2/id3v1 tags found',
    ],

    'title' => [
        'resort' => 'MP3 File Resorting',
        'deduplicate' => 'Audio Files Deduplication',
    ],

    'warning' => [
        'file_skipped' => 'Skipped file :file: :message',
        'normalize_collisions_found' => 'Name normalization collisions found: :count',
        'normalize_collision' => 'Collision: :from conflicts with existing :to (duplicates with different parameters).',
    ],

    'success' => [
        'resorted' => 'MP3 resorting completed!',
        'deduplicated' => 'Audio deduplication completed!',
        'processed' => 'Processed files: :processed',
        'errors' => 'Skipped (errors): :errors',
    ],

    'info' => [
        'dir_created' => 'Created destination directory: :path',
        'moved' => 'Moved file: :file -> :dest',
        'deleted' => 'Deleted file: :file',
        'renamed' => 'Renamed: :from -> :to',
    ],

    'note' => [
        'artist_folder_created' => 'Created artist folder: :folder',
        'artist_folder_created_dry' => 'Would create artist folder: :folder',
        'dir_created_dry' => 'Would create destination directory: :path',
        'dry_moved' => 'Would move file: :file -> :dest',
        'dry_deleted' => 'Would delete file: :file',
        'dry_renamed' => 'Would rename: :from -> :to',
    ],

    'exception' => [
        'context_not_initialized' => 'Context not initialized: :fields',
    ],

    'fallback' => [
        'unknown_artist_folder' => 'Unknown_Artist',
        'unknown_file_name' => 'unknown_file.mp3',
    ],
];

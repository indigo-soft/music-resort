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
        'migrate' => [
            'description' => 'Apply pending database migrations',
            'help' => 'Validates the database connection and applies any pending .sql migrations from db/migrations/ in order. Each migration runs inside a transaction — on failure the transaction is rolled back.',
        ],
        'migrate_refresh' => [
            'description' => 'Drop all non-system tables and re-run all migrations from scratch',
            'help' => 'Drops all tables except those listed in MIGRATION_PRESERVE_TABLES (default: migrations, processing_log), clears the migrations log, then re-applies all migrations.',
        ],
        'metadata_enrich' => [
            'description' => 'Fetch Last.fm artist tags for the collection and cache them in the database',
            'help' => 'Collects distinct artists from music_file_metadata, fetches their top tags from the Last.fm API, and caches the result in lastfm_artist_tags. Fresh cache entries (within LASTFM_CACHE_TTL_DAYS) are skipped unless --force is given. Use --limit=N to process only the first N artists. Failed artists are retried once, then skipped with a warning.',
        ],
        'metadata_scan' => [
            'description' => 'Scan the music collection and store file metadata in the database',
            'help' => 'Walks the source directory, reads each audio file (mp3/flac/m4a) via getID3, and upserts one row per file into music_file_metadata. This inventory is the artist source for metadata:enrich, so run a scan first. Unreadable files are marked and skipped. Use --limit=N to scan only the first N files.',
        ],
    ],

    'arg' => [
        'source' => 'Source directory with MP3 files',
        'destination' => 'Destination directory for sorted files',
    ],

    'opt' => [
        'dry_run' => 'Simulate without making filesystem changes',
        'enrich_force' => 'Re-fetch tags even when a fresh cache entry exists',
        'enrich_limit' => 'Process at most N artists',
        'scan_limit' => 'Scan at most N files',
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
        'no_genre' => 'Unkown genre',
        'db_path_not_set' => 'DB_PATH is not set in .env. Add DB_PATH=./db/database/music.sqlite to your .env file.',
        'db_dir_not_found' => 'Database directory does not exist: :path',
        'db_not_writable' => 'Database is not writable: :error',
        'migrate_failed' => 'Migration failed: :filename — :error',
        'lastfm_key_missing' => 'LASTFM_API_KEY is not set in .env. Create a key at https://www.last.fm/api/account/create and add LASTFM_API_KEY=... to your .env file.',
    ],

    'title' => [
        'resort' => 'MP3 File Resorting',
        'deduplicate' => 'Audio Files Deduplication',
    ],

    'warning' => [
        'file_skipped' => 'Skipped file :file: :message',
        'normalize_collisions_found' => 'Name normalization collisions found: :count',
        'normalize_collision' => 'Collision: :from conflicts with existing :to (duplicates with different parameters).',
        'migrate_refresh_preserved' => 'Preserved tables: :tables',
        'enrich_failed' => 'Failed to fetch tags for :artist (retried once, skipped)',
        'scan_unreadable' => 'Could not read :file (marked unreadable, skipped)',
    ],

    'success' => [
        'resorted' => 'MP3 resorting completed!',
        'deduplicated' => 'Audio deduplication completed!',
        'processed' => 'Processed files: :processed',
        'errors' => 'Skipped (errors): :errors',
        'removed_empty_dirs' => 'Removed empty directories: :count',
        'migrate_done' => ':count migration(s) applied successfully.',
        'migrate_none' => 'No pending migrations. Database is up to date.',
        'migrate_refresh_done' => 'Refresh complete. :count migration(s) applied.',
        'enrich_done' => 'Enrichment complete. Artists: :total, fetched: :fetched, cached: :cached, no tags: :empty, failed: :failed.',
        'scan_done' => 'Scan complete. Files: :total, scanned: :scanned, unreadable: :unreadable.',
    ],

    'info' => [
        'dir_created' => 'Created destination directory: :path',
        'moved' => 'Moved file: :file -> :dest',
        'deleted' => 'Deleted file: :file',
        'renamed' => 'Renamed: :from -> :to',
        'dir_deleted' => 'Deleted empty directory: :path',
        'migrate_applied' => 'Applied: :filename',
        'migrate_refresh_dropped' => 'Dropped :count table(s). Running migrations…',
        'enrich_fetched' => 'Fetched :count tag(s) for :artist',
        'enrich_empty' => 'No tags on Last.fm for :artist',
        'enrich_cached' => 'Cache is fresh for :artist (skipped)',
        'scan_file' => 'Scanned: :file',
    ],

    'note' => [
        'artist_folder_created' => 'Created artist folder: :folder',
        'artist_folder_created_dry' => 'Would create artist folder: :folder',
        'dir_created_dry' => 'Would create destination directory: :path',
        'dry_moved' => 'Would move file: :file -> :dest',
        'dry_deleted' => 'Would delete file: :file',
        'dry_renamed' => 'Would rename: :from -> :to',
        'dry_dir_deleted' => 'Would delete empty directory: :path',
    ],

    'exception' => [
        'context_not_initialized' => 'Context not initialized: :fields',
    ],

    'fallback' => [
        'unknown_artist_folder' => 'Unknown_Artist',
        'unknown_file_name' => 'unknown_file.mp3',
    ],
];

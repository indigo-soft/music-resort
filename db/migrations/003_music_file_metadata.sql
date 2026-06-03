CREATE TABLE IF NOT EXISTS music_file_metadata (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    file_path    TEXT    NOT NULL UNIQUE,
    status       TEXT    NOT NULL DEFAULT 'active',
    format       TEXT,
    duration     REAL,
    bitrate      INTEGER,
    file_size    INTEGER,
    title        TEXT,
    artist       TEXT,
    album        TEXT,
    album_artist TEXT,
    track_number TEXT,
    year         INTEGER,
    genre        TEXT,
    comment      TEXT,
    tag_source   TEXT,
    scanned_at   TEXT,
    created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_mfm_status
    ON music_file_metadata (status);

CREATE INDEX IF NOT EXISTS idx_mfm_artist
    ON music_file_metadata (artist);

CREATE INDEX IF NOT EXISTS idx_mfm_album_artist
    ON music_file_metadata (album_artist);

CREATE INDEX IF NOT EXISTS idx_mfm_scanned_at
    ON music_file_metadata (scanned_at);

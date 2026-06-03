CREATE TABLE IF NOT EXISTS audio_processing (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    original_path   TEXT    NOT NULL,
    processed_path  TEXT,
    status          TEXT    NOT NULL DEFAULT 'pending',
    operation       TEXT    NOT NULL,
    duration_before REAL,
    duration_after  REAL,
    size_before     INTEGER,
    size_after      INTEGER,
    error_message   TEXT,
    processed_at    TEXT,
    created_at      TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_ap_status
    ON audio_processing (status);

CREATE INDEX IF NOT EXISTS idx_ap_original_path
    ON audio_processing (original_path);

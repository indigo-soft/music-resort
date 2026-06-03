CREATE TABLE IF NOT EXISTS processing_log (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    command    TEXT    NOT NULL,
    level      TEXT    NOT NULL,
    message    TEXT    NOT NULL,
    context    TEXT,
    run_id     TEXT    NOT NULL,
    created_at TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_pl_run_id
    ON processing_log (run_id);

CREATE INDEX IF NOT EXISTS idx_pl_level
    ON processing_log (level);

CREATE INDEX IF NOT EXISTS idx_pl_command
    ON processing_log (command);

CREATE INDEX IF NOT EXISTS idx_pl_created_at
    ON processing_log (created_at);

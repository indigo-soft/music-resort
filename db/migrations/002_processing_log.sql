CREATE TABLE IF NOT EXISTS processing_log (
    id         INT          NOT NULL AUTO_INCREMENT,
    command    VARCHAR(100) NOT NULL,
    level      VARCHAR(10)  NOT NULL,
    message    TEXT         NOT NULL,
    context    TEXT,
    run_id     CHAR(36)     NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_pl_run_id     ON processing_log (run_id);
CREATE INDEX idx_pl_level      ON processing_log (level);
CREATE INDEX idx_pl_command    ON processing_log (command);
CREATE INDEX idx_pl_created_at ON processing_log (created_at);

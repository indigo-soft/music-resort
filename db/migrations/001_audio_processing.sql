CREATE TABLE IF NOT EXISTS audio_processing (
    id              INT           NOT NULL AUTO_INCREMENT,
    original_path   VARCHAR(500)  NOT NULL,
    processed_path  VARCHAR(500),
    status          VARCHAR(20)   NOT NULL DEFAULT 'pending',
    operation       VARCHAR(50)   NOT NULL,
    duration_before DOUBLE,
    duration_after  DOUBLE,
    size_before     BIGINT,
    size_after      BIGINT,
    error_message   TEXT,
    processed_at    DATETIME,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_ap_status        ON audio_processing (status);
CREATE INDEX idx_ap_original_path ON audio_processing (original_path);

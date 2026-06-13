CREATE TABLE IF NOT EXISTS music_file_metadata (
    id           INT          NOT NULL AUTO_INCREMENT,
    file_path    VARCHAR(500) NOT NULL,
    status       VARCHAR(20)  NOT NULL DEFAULT 'active',
    format       VARCHAR(20),
    duration     DOUBLE,
    bitrate      INT,
    file_size    BIGINT,
    title        TEXT,
    artist       VARCHAR(255),
    album        VARCHAR(255),
    album_artist VARCHAR(255),
    track_number VARCHAR(20),
    year         SMALLINT,
    genre        VARCHAR(100),
    comment      TEXT,
    tag_source   VARCHAR(20),
    scanned_at   DATETIME,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mfm_file_path (file_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_mfm_status       ON music_file_metadata (status);
CREATE INDEX idx_mfm_artist       ON music_file_metadata (artist);
CREATE INDEX idx_mfm_album_artist ON music_file_metadata (album_artist);
CREATE INDEX idx_mfm_scanned_at   ON music_file_metadata (scanned_at);

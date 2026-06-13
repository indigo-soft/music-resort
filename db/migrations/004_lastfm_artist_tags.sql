CREATE TABLE IF NOT EXISTS lastfm_artist_tags (
    id         INT          NOT NULL AUTO_INCREMENT,
    artist     VARCHAR(255) NOT NULL,
    tags       JSON         NOT NULL,
    fetched_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_lat_artist (artist)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_lat_fetched_at ON lastfm_artist_tags (fetched_at);

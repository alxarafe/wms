CREATE TABLE IF NOT EXISTS greetings (
    id          VARCHAR(64) PRIMARY KEY,
    message     TEXT NOT NULL,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

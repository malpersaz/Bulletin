CREATE TABLE mod_video (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lang TEXT NOT NULL,
    name TEXT NOT NULL,
    duration TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now')),
    status TEXT NOT NULL DEFAULT 'y',     -- ENUM yerine TEXT
    description TEXT DEFAULT NULL,
    image TEXT,
    flv TEXT,
    original TEXT DEFAULT NULL,
    acl_id INTEGER DEFAULT 0,
    user_id INTEGER NOT NULL
);
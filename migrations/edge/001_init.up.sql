CREATE TABLE `user_main` (
     `UID` INTEGER PRIMARY KEY,
     `company_id` INTEGER NOT NULL DEFAULT 1,
     `last_access` TIMESTAMP DEFAULT NULL,
     `login_time` TIMESTAMP DEFAULT NULL,
     `num_logins` INTEGER NOT NULL DEFAULT 0,
     `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     `status` INTEGER NOT NULL DEFAULT 0,
     `locale` CHAR(5) COLLATE NOCASE DEFAULT NULL,
     `username` CHAR(50) COLLATE NOCASE DEFAULT '',
     `password` CHAR(60) DEFAULT NULL,
     `session_id` VARCHAR(60) DEFAULT NULL,
     `gender` VARCHAR(10) COLLATE NOCASE DEFAULT NULL,
     `email` VARCHAR(100) COLLATE NOCASE NOT NULL DEFAULT '',
     `last_password_change` TIMESTAMP DEFAULT NULL,
     UNIQUE (`email`)
);
INSERT INTO `user_main`
(`company_id`, `last_access`, `login_time`, `num_logins`, `created_at`, `status`, `locale`, `username`, `password`, `gender`, `email`, `last_password_change`)
VALUES
    (1, CURRENT_TIMESTAMP, NULL, 0, CURRENT_TIMESTAMP, 9, 'en_US', 'admin', '$2y$10$GNIvEOnYy5OxEfdnMO0O0O2g1myLht2CTK4SaVfMK664O85Sd4MA6', '', 'example@example.com', NULL);

CREATE TABLE `user_acl` (
    `UID` INTEGER,
    `acl` INTEGER NOT NULL DEFAULT 0,
    `module` VARCHAR(20) DEFAULT NULL,
    FOREIGN KEY (`UID`) REFERENCES `user_main` (`UID`) ON DELETE CASCADE
);
INSERT INTO `user_acl` (`UID`, `acl`, `module`) VALUES (1, 2, 'users');
INSERT INTO `user_acl` (`UID`, `acl`, `module`) VALUES (1, 8, 'mediapool');
INSERT INTO `user_acl` (`UID`, `acl`, `module`) VALUES (1, 8, 'player');
INSERT INTO `user_acl` (`UID`, `acl`, `module`) VALUES (1, 8, 'playlists');

CREATE TABLE oauth2_clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id TEXT UNIQUE NOT NULL,
    client_name TEXT UNIQUE NOT NULL,
    client_secret TEXT DEFAULT NULL,
    redirect_uri TEXT NOT NULL,
    grant_type TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO oauth2_clients (client_id, client_name, client_secret, redirect_uri, grant_type)
VALUES ('edge-default-client', 'The Default Client', '$2y$10$GNIvEOnYy5OxEfdnMO0O0O2g1myLht2CTK4SaVfMK664O85Sd4MA6',
        'https://oauth2client.ddev.site/callback.php', 'authorization_code refresh_token');

CREATE TABLE oauth2_credentials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL, -- 'auth_code', 'access_token', 'refresh_token'
    token TEXT NOT NULL,
    client_id TEXT NOT NULL,
    UID INTEGER NOT NULL,
    redirect_uri TEXT DEFAULT NULL,
    scopes TEXT DEFAULT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked INTEGER DEFAULT 0,
    FOREIGN KEY (UID) REFERENCES user_main(UID) ON DELETE CASCADE
);

CREATE TABLE oauth2_scopes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    scope TEXT NOT NULL UNIQUE,
    description TEXT DEFAULT NULL
);

CREATE TABLE oauth2_client_consent (
     UID INT NOT NULL,
     client_id VARCHAR(255) NOT NULL,
     consented_at DATETIME NOT NULL,
     PRIMARY KEY (UID, client_id)
);

CREATE TABLE mediapool_nodes (
    node_id INTEGER PRIMARY KEY AUTOINCREMENT,
    root_id INTEGER NOT NULL DEFAULT 0,
    parent_id INTEGER NOT NULL DEFAULT 0,
    level INTEGER NOT NULL,
    root_order INTEGER NOT NULL,
    is_user_folder INTEGER NOT NULL DEFAULT 0,
    lft INTEGER NOT NULL DEFAULT 0,
    rgt INTEGER NOT NULL DEFAULT 0,
    UID INTEGER NOT NULL DEFAULT 0,
    visibility INTEGER NOT NULL DEFAULT 0,
    last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    create_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `name` TEXT COLLATE NOCASE NOT NULL DEFAULT '',
    media_location TEXT COLLATE NOCASE NOT NULL CHECK(media_location IN ('internal', 'dropbox', 'azure', 'google', 'webdav', 's3',
                                                          'ftp')) DEFAULT `internal`,
    credentials TEXT
);
CREATE INDEX idx_mediapool_nodes_root_id ON mediapool_nodes (root_id);
-- set some default root dirs.
INSERT INTO mediapool_nodes (root_id, parent_id, level, root_order, lft, rgt, UID, visibility, last_updated, create_date, name)
VALUES (1, 0, 1, 1, 1, 12, 1, 2, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 'public');

INSERT INTO mediapool_nodes (root_id, parent_id, level, root_order, is_user_folder, lft, rgt, UID, visibility,
                             last_updated, create_date, name)
 VALUES (2, 0, 1, 2, 1, 1, 4, 1, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 'admin');

-- set some default dirs under public
INSERT INTO mediapool_nodes (root_id, parent_id, level, root_order, lft, rgt, UID, visibility, last_updated, create_date, name)
VALUES (1, 1, 2, 1, 2, 3, 1, 2, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 'images');
INSERT INTO mediapool_nodes (root_id, parent_id, level, root_order, lft, rgt, UID, visibility, last_updated, create_date, name)
VALUES (1, 1, 2, 1, 4, 5, 1, 2, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 'videos');
INSERT INTO mediapool_nodes (root_id, parent_id, level, root_order, lft, rgt, UID, visibility, last_updated, create_date, name)
VALUES (1, 1, 2, 1, 6, 7, 1, 2, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 'widgets');
INSERT INTO mediapool_nodes (root_id, parent_id, level, root_order, lft, rgt, UID, visibility, last_updated, create_date, name)
VALUES (1, 1, 2, 1, 8, 9, 1, 2, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 'documents');
INSERT INTO mediapool_nodes (root_id, parent_id, level, root_order, lft, rgt, UID, visibility, last_updated, create_date, name)
VALUES (1, 1, 2, 1, 10, 11, 1, 2, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 'firmware');

CREATE TABLE mediapool_files (
     media_id CHAR(36) PRIMARY KEY, -- UUID as Text field
     node_id INTEGER NOT NULL,
     deleted INTEGER NOT NULL DEFAULT 0,
     UID INTEGER NOT NULL DEFAULT 0,
     upload_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
     checksum CHAR(64) NOT NULL, -- SHA-256 Hash of the file content
     mimetype VARCHAR(50) COLLATE NOCASE NOT NULL,
     metadata TEXT DEFAULT NULL, --json encoded metadata
     tags TEXT DEFAULT NULL,
     filename TEXT COLLATE NOCASE DEFAULT NULL,
     extension varchar(10) DEFAULT NULL,
     thumb_extension varchar(10) DEFAULT NULL,
     media_description TEXT DEFAULT NULL
);

CREATE INDEX idx_mediapool_node_id ON mediapool_files (node_id);
CREATE INDEX idx_mediapool_checksum ON mediapool_files (checksum);
CREATE INDEX idx_mediapool_mimetype ON mediapool_files (mimetype);
CREATE INDEX idx_mediapool_deleted ON mediapool_files (deleted);

CREATE TABLE playlists (
    playlist_id INTEGER PRIMARY KEY AUTOINCREMENT,
    UID INTEGER NOT NULL DEFAULT 0,
    time_limit INTEGER NOT NULL DEFAULT 0,
    owner_duration INTEGER NOT NULL DEFAULT 0,
    duration INTEGER NOT NULL DEFAULT 0,
    filesize INTEGER NOT NULL DEFAULT 0,
    shuffle INTEGER NOT NULL DEFAULT 0,
    shuffle_picking INTEGER NOT NULL DEFAULT 0,
    last_update TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    playlist_mode TEXT COLLATE NOCASE DEFAULT 'master' CHECK (playlist_mode IN ('master', 'internal', 'external', 'multizone', 'channel')),
    playlist_name varchar(100) COLLATE NOCASE DEFAULT NULL,
    external_playlist_link  varchar(100) DEFAULT NULL,
    multizone TEXT DEFAULT NULL
);

CREATE TABLE playlists_items (
    item_id INTEGER PRIMARY KEY AUTOINCREMENT,
    playlist_id INTEGER NOT NULL DEFAULT 0,
    UID INTEGER NOT NULL DEFAULT 0,
    item_duration INTEGER NOT NULL DEFAULT 0,
    item_filesize INTEGER NOT NULL DEFAULT 0,
    item_order INTEGER NOT NULL DEFAULT 0,
    foreign_id INTEGER NOT NULL DEFAULT 0, -- playlists and channels
    last_update TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    item_type CHAR(12) NOT NULL DEFAULT 'media', -- enum in Mariadb 'media', 'media_url', 'playlist', 'playlist_url', 'template', 'channel'
    file_resource CHAR(36) NOT NULL DEFAULT '', -- file or symlink name for media and templates
    datasource VARCHAR(8) COLLATE NOCASE DEFAULT 'file' CHECK (datasource IN ('file', 'stream', 'video_in')),
    media_type VARCHAR(12) COLLATE NOCASE DEFAULT 'image' CHECK (media_type IN ('image', 'video', 'audio', 'widget', 'html', 'text', 'document', 'application')),
    item_name TEXT NOT NULL DEFAULT '',
    conditional TEXT,
    properties TEXT, -- scaling, position, name, flags like locked, disabled, loggable, categories
    content_data TEXT, -- depends on item and media type: can be url or Widget parameters
    begin_trigger TEXT,
    end_trigger TEXT
);

CREATE INDEX idx_playlist_id ON playlists_items (playlist_id, item_order);
CREATE INDEX idx_item_type_resource ON playlists_items (item_type, file_resource);
CREATE INDEX idx_item_type_id ON playlists_items (item_type, foreign_id);

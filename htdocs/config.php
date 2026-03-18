<?php
// ============================================================
//  config.php
// ============================================================

// Database
define('DB_HOST',    'sql300.infinityfree.com');
define('DB_NAME',    'if0_41292608_nexus');
define('DB_USER',    'if0_41292608');
define('DB_PASS',    '3ktmubJeIfVtVB7');
define('DB_CHARSET', 'utf8mb4');

// Sessione
define('SESSION_NAME',     'nexus_sess');
define('SESSION_LIFETIME', 3600);

// Brute-force protection
define('MAX_LOGIN_ATTEMPTS',    5);
define('LOCK_DURATION_MINUTES', 15);

// URL base
define('BASE_URL', 'https://nexus-music.xo.je');

// Email SMTP (Gmail)
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USERNAME', 'nexusmusicnoreply@gmail.com');
define('MAIL_PASSWORD', 'azlr gtbn chgc aivb');
define('MAIL_FROM',     'nexusmusicnoreply@gmail.com');
define('MAIL_FROM_NAME','Nexus Music');

// Last.fm API
define('LASTFM_API_KEY',    '6b8e9a2a5c802a4714077fa9e5fc2cfb');
define('LASTFM_API_SECRET', 'f05edb111c4e6bc2414502358d9ce8cc');
define('LASTFM_BASE_URL',   'https://ws.audioscrobbler.com/2.0/');
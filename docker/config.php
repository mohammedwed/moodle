<?php
// Docker runtime config — placed at the repo root (config.php) at build time.
// public/config.php proxies here via require_once.
// All values are driven by environment variables set in docker-compose.yml / .env.
unset($CFG);
global $CFG;
$CFG = new stdClass();

// Database
$CFG->dbtype    = getenv('MOODLE_DB_TYPE')  ?: 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost    = getenv('MOODLE_DB_HOST')  ?: 'db';
$CFG->dbname    = getenv('MOODLE_DB_NAME')  ?: 'moodle';
$CFG->dbuser    = getenv('MOODLE_DB_USER')  ?: 'moodle';
$CFG->dbpass    = getenv('MOODLE_DB_PASS')  ?: 'moodle';
$CFG->prefix    = 'mdl_';

// Paths — this file lives at the repo root; web code lives in public/
$CFG->wwwroot   = rtrim(getenv('MOODLE_WWWROOT') ?: 'http://localhost', '/');
$CFG->dataroot  = getenv('MOODLE_DATAROOT') ?: '/var/moodledata';
$CFG->dirroot   = __DIR__ . '/public';
$CFG->admin     = 'admin';

$CFG->directorypermissions   = 0777;
$CFG->disableupdateautodeploy = true;

// Tell Moodle it sits behind an SSL-terminating reverse proxy (Caddy).
// Without this, Moodle sees HTTP internally and loops redirecting to HTTPS.
if (str_starts_with($CFG->wwwroot, 'https://')) {
    $CFG->sslproxy = true;
}

// SendGrid Web API — routes via HTTPS (port 443), no outbound SMTP needed.
// The sendgrid-sendmail binary in the container reads SENDGRID_API_KEY from env.
if (getenv('SENDGRID_API_KEY')) {
    $CFG->smtphosts      = '';   // empty = PHP mail() → sendmail_path → our wrapper
    $CFG->noreplyaddress = getenv('MOODLE_NOREPLY') ?: 'noreply@' . parse_url($CFG->wwwroot, PHP_URL_HOST);
}

// Redis session store (optional — only wired up when MOODLE_REDIS_HOST is set)
if ($redisHost = getenv('MOODLE_REDIS_HOST')) {
    $CFG->session_handler_class = '\core\session\redis';
    $CFG->session_redis_host    = $redisHost;
    $CFG->session_redis_port    = (int)(getenv('MOODLE_REDIS_PORT') ?: 6379);
}

require_once(__DIR__ . '/public/lib/setup.php');

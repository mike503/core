<?php
// DATABASE

$config['database']['hostname'] = '## DB HOST ##';
$config['database']['username'] = '## DB USER ##';
$config['database']['password'] = '## DB PASS ##';
$config['database']['database'] = '## DB NAME ##';
$config['database']['port'] = 3306;
$config['database']['socket'] = '/var/run/mysqld/mysqld.sock';

// SITE CONFIGURATION

// "secret" key used for our digest hashing stuff. steal one: https://api.wordpress.org/secret-key/1.1/salt/
$config['salt'] = '##GENERATE ME##';

// used often in the <title> tag and email templates.
$config['site_name'] = '##YOUR SITE NAME##';

$config['site_theme'] = '## THEME NAME ##';

// used in the email Return-Path ("envelope address" under the hood) and a fallback.
$config['site_email'] = '##YOUR SITE EMAIL##';

// used in links that are derived using the router lookup. leave empty if not hosted in a subdirectory. no trailing slash!
$config['base_path'] = '##YOUR SITE EMAIL##';

// used in templates that are exposed to the public. leave empty if not hosted in a subdirectory. no trailing slash!
$config['asset_path'] = '##YOUR SITE EMAIL##';

// COOKIES AND SESSIONS

// used for session and any other cookies,
$config['cookie_domain'] = 'foo.com';

// overrides PHP's built-in "PHPSESSID"
$config['session_name'] = 'session';

// LOGGING

// log targets. can select any combination (or none.)
// * database = core_log table
// * file = PROJECT_ROOT/var/app.log
// * syslog = php syslog()
$config['log'] = array(
    'database',
    'file',
    'syslog',
);

// if you want a LOT of verbosity, turn this on.
$config['superdebug'] = FALSE;

// CACHING

// future ideas: memcached, redis, file, apc, opcache
$config['cache'] = 'memcache';
$config['memcache_key_prefix'] = '##EDIT##';
$config['memcache_servers'] = array(
    '127.0.0.1:11211',
);

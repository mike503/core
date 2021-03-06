<?php
function core_session_close() {
    return TRUE;
}

// @TODO - could translate the id into an integer for speed?
function core_session_die($id = '') {
    core_db_query("DELETE FROM core_session WHERE session_id='$id'");
    return TRUE;
}

function core_session_gc($maxlifetime = 0) {
    return TRUE;
}

function core_session_init() {
/*
; OWASP suggestions
;session.cache_expire = 30
;session.hash_function = 1
;session.hash_bits_per_character = 6
;session.use_trans_sid = 0
;session.cookie_lifetime = 0
;session.cookie_httponly = 1
*/
    ini_set('session.use_only_cookies', 1);
    ini_set('session.gc_probability', 0);
// redundant to below?
    ini_set('session.save_handler', 'user');
    ini_set('session.cookie_domain', core_registry_get('config.cookie_domain', $_SERVER['HTTP_HOST']));
    session_name(core_registry_get('config.session_name', 'PHPSESSID'));
    session_set_save_handler('core_session_open', 'core_session_close', 'core_session_read', 'core_session_write', 'core_session_die', 'core_session_gc');
    session_start();
    register_shutdown_function('session_write_close');
    // form data is saved in a "state"
    foreach ($_POST as $key => $val) {
        $_SESSION['state']['form_field_previous'][$key] = $val;
    }
    // "destination" is used in various places
    if (isset($request['params']['destination'])) {
        $_SESSION['destination'] = $request['params']['destination'];
    }
}

function core_session_open($path = '', $name = '') {
    return TRUE;
}

// @TODO - could translate the id into an integer for speed?
function core_session_read($id = '') {
    $return = '';
    $q = core_db_query("SELECT session_data FROM core_session WHERE session_id='$id'");
    if (core_db_numrows($q) == 1) {
        list($return) = core_db_rows($q);
    }
    core_db_free($q);
    return $return;
}

// @TODO - could translate the id into an integer for speed?
function core_session_write($id = '', $data = '') {
    return core_db_query("INSERT INTO core_session (session_id, session_data, session_access_datetime) VALUES('$id', '" . core_db_escape($data) . "', " . core_timestamp() . ") ON DUPLICATE KEY UPDATE session_data='" . core_db_escape($data) . "', session_access_datetime=" . core_timestamp());
}

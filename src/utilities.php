<?php
// @NOTE - currently does not support attachments.
// @NOTE - $headers['to'] can accept "Some User <foo@bar.com>" and also multiple recipients.
// @TODO - additional headers we could look at using: "Reply-To:" "Content-type:" "MIME-Version:"
// @TODO - might want to reformat body to make sure all line endings are CRLF (\r\n)
// @TODO - do I like to use the "-f" at the end? should it also be the "From"
function core_email($type = '', $headers = array(), $tokens = array()) {

    if (!isset($headers['to']) || !core_validate_email($headers['to'])) {
        core_log('mail', 'cannot send email to an invalid address', 'error');
        return FALSE;
    }
    if (!isset($headers['subject']) || empty($headers['subject'])) {
        core_log('mail', 'cannot send email to an invalid address', 'error');
        return FALSE;
    }

    // define a unique mail ID for tracing this back.
    $mail_id = date('Ymd') . substr(md5($headers['to'] . $GLOBALS['config']['salt']) . md5(time() . $GLOBALS['config']['salt']), 0, 40);

    $headers['from'] = isset($headers['from']) ? $headers['from'] : $GLOBALS['config']['site_email'];
    $additional_headers = 'From: ' . $headers['from'] . "\r\n";
    $additional_headers .= 'X-Mail-ID: ' . $mail_id . "\r\n";
    if (isset($headers['cc'])) {
        $additional_headers .= 'Cc: ' . $headers['cc'] . "\r\n";
    }
    if (isset($headers['bcc'])) {
        $additional_headers .= 'Bcc: ' . $headers['bcc'] . "\r\n";
    }

// @TODO - have not tested this since the remap
    $template = $GLOBALS['config']['theme_root'] . DIRECTORY_SEPARATOR . 'emails' . DIRECTORY_SEPARATOR . $type . '.txt';
    if (!file_exists($template)) {
        core_log('mail', 'email template missing: ' . $template, 'error');
        return FALSE;
    }

    // body replacement.
    $body = file_get_contents($template);
    foreach ($tokens as $key => $value) {
        $body = str_replace('%%' . $key . '%%', $value, $body);
    }
    $body .= PHP_EOL;
    $body .= 'Mail ID: ' . $mail_id . PHP_EOL;

    core_log('mail', 'mail-id: ' . $mail_id . ' headers: ' . print_r($headers, TRUE), 'info');
    if (!mail($headers['to'], $headers['subject'], $body, $additional_headers, '-f' . $GLOBALS['config']['site_email'])) {
        core_log('mail', 'mail-id: ' . $mail_id . ' failed to send', 'error');
        return FALSE;
    }
    return TRUE;
}

function core_form_previous($name = '', $encode = TRUE) {
    $return = '';
    // check the session first - previously attempted submission.
    if (isset($_SESSION['state']['form_field_previous'][$name])) {
        $return = $_SESSION['state']['form_field_previous'][$name];
    } elseif (isset($GLOBALS['form_field_previous'][$name])) {
      // next check the globals, no previous form submission. populated by the database, probably.
      $return = $GLOBALS['form_field_previous'][$name];
    }
    return $encode ? htmlspecialchars($return) : $return;
}

function core_message_set($type = '', $message = '') {
    // add to session message stack
    $_SESSION['state']['messages'][$type][] = $message;
}

function core_message_get($type = '') {
    $return = '';
    if (!empty($type) && isset($_SESSION['state']['messages'][$type]) && is_array($_SESSION['state']['messages'][$type])) {
        $return = $_SESSION['state']['messages'][$type];
        unset($_SESSION['state']['messages'][$type]);
    } elseif (isset($_SESSION['state']['messages'])) {
        $return = $_SESSION['state']['messages'];
    }
    unset($_SESSION['state']['messages']);
    return $return;
}

function core_destination($url = '') {
    if (empty($url)) {
        if (isset($GLOBALS['request']['params']['destination'])) {
            $url = $GLOBALS['request']['params']['destination'];
        } elseif (isset($_SESSION['destination'])) {
            $url = $_SESSION['destination'];
            unset($_SESSION['destination']);
        } else {
            $url = !empty($GLOBALS['request']['base']) ? $GLOBALS['request']['base'] : '/';
        }
    }
    return $url;
}

// should go in theme layer, probably
function core_form_highlight($name = '') {
    if (isset($_SESSION['state']['form_field_error'][$name])) {
// @TODO - useless with bootstrap. http://getbootstrap.com/css/#forms-control-validation
        unset($_SESSION['state']['form_field_error'][$name]);
    }
}

// this might change to be a microtime instead. $_SERVER['REQUEST_TIME_FLOAT'] or surgery on PHP's microtime()
function core_timestamp() {
    if (isset($_SERVER['REQUEST_TIME'])) {
        return $_SERVER['REQUEST_TIME'];
    }
    return time();
}

// this function captures both PHP-generated errors and notices, as well as our own core_log() messages.
function core_log_commit($details = array()) {

    if (!$logging = core_config_get('log', FALSE)) {
        return FALSE;
    }

    global $config;

    // our tag => syslog mapping. see http://php.net/manual/en/function.syslog.php
    $message_levels = array(
        'fatal' => LOG_CRIT, // LOG_ALERT or LOG_EMERG could also be an option
        'error' => LOG_ERR,
        'warning' => LOG_WARNING,
        'notice' => LOG_NOTICE,
        'info' => LOG_INFO,
        'debug' => LOG_DEBUG,
    );

    // normalize the PHP error levels.
    if ($details['type'] == 'php') {
        switch ($details['level']) {
            case E_CORE_ERROR:
            case E_ERROR:
                $details['level'] = 'fatal';
                break;
            case E_RECOVERABLE_ERROR:
            case E_USER_ERROR:
                $details['level'] = 'error';
                break;
            case E_USER_WARNING:
            case E_CORE_WARNING:
            case E_WARNING:
                $details['level'] = 'warning';
                break;
            case E_USER_NOTICE:
            case E_NOTICE:
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $details['level'] = 'notice';
                break;
            default:
                core_log('log', 'unknown message level type from PHP error handler: ' . $details['level'], 'notice');
                break;
        }
    } elseif (!isset($message_levels[$details['level']])) {
        $details['level'] = 'debug';
    }

    $details['function'] = isset($details['function']) ? $details['function'] : '';
    $details['url'] = isset($GLOBALS['request']['url']) ? $GLOBALS['request']['url'] : '';
    $details['id'] = isset($GLOBALS['request']['id']) ? $GLOBALS['request']['id'] : '';
    $details['user_id'] = isset($GLOBALS['user']['user_id']) ? $GLOBALS['user']['user_id'] : 0;
    $details['ip'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $details['referrer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] :'';

// @TODO - use "html" instead? or "output" or "console"?
    if (in_array('display', $logging)) {
// @TODO - clean this up
        switch ($details['level']) {
            case 'fatal':
                $color = 'red';
                break;
            case 'error':
                $color = 'red';
                break;
            case 'warning':
                $color = 'orange';
                break;
            case 'notice':
                $color = 'orange';
                break;
            case 'debug':
                $color = 'black';
                break;
        }
        echo '<div style="border: 1px solid ' . $color . '; padding: 10px; margin-bottom: 10px;">';
        echo 'Error:<br />';
        echo 'level: ' . $details['level'] . '<br />';
        echo 'type: ' . $details['type'] . '<br />';
#        echo 'function: ' . $details['function'] . '()<br />';
#        echo 'file/line: ' . $details['file'] . ':' . $details['line'] . '<br />';
        echo 'message: ' . $details['message'] . '<br />';
        echo '</div>';
    }

    if (in_array('file', $logging)) {
        $dir = $config['project_root'] . DIRECTORY_SEPARATOR . 'var';
        if (!is_dir($dir)) {
// @TODO - figure out a cleaner way without having to "@"
            @mkdir($dir, 0711, TRUE);
        }
        $file = $dir . DIRECTORY_SEPARATOR . 'app.log';
// @TODO - figure out a cleaner way without having to "@"
        @file_put_contents($file, print_r($details, TRUE), FILE_APPEND);
    }

    if (in_array('syslog', $logging)) {
        openlog('php/core', LOG_ODELAY | LOG_PID, LOG_USER);
        syslog($message_levels[$details['level']], $details['message']);
    }

    if (in_array('database', $logging)) {
// need to check the db is available!
        core_db_query("INSERT INTO core_log (log_timestamp, log_type, log_level, log_file, log_line, log_function, log_request_id, log_url, log_referrer, log_ip, log_user_id, log_message) VALUES (" . core_timestamp() . ", '" . core_db_escape($details['type']). "', '" . core_db_escape($details['level']) . "', '" . core_db_escape($details['file']) . "', '" . intval($details['line']). "', '" . core_db_escape($details['function']). "', '" . core_db_escape($details['id']) . "', '" . core_db_escape($details['url']) . "', '" . core_db_escape($details['referrer']). "', '" . core_db_escape($details['ip']). "', " . intval($details['user_id']). ", '" . core_db_escape($details['message']). "')");
    }
}

// @TODO we're still not getting certain PHP errors.
function core_error_handler($level = 0, $message = '', $file = '', $line = 0, $context = array()) {
    $message = $message . PHP_EOL;
    $message .= 'Stack trace:';
    foreach (debug_backtrace() as $step) {
        if (isset($step['file']) && isset($step['line'])) {
            $message .= PHP_EOL . $step['file'] . ':' . $step['line'];
        }
    }
    $details = array(
        'timestamp' => core_timestamp(),
        'type' => 'php',
        'message' => $message,
        'level' => $level, // @TODO - not useful, yet
        'file' => $file,
        'line' => $line,
    );
    core_log_commit($details);
}

// our own central logging function.
function core_log($type = '', $message = '', $level = 'debug') {
    $count = count(debug_backtrace());
    $function = '(global)';
    $file = debug_backtrace()[0]['file'];
    $line = debug_backtrace()[0]['line'];
    if ($count > 1) {
        $function = debug_backtrace()[$count - 1]['function'];
        if ($function == 'require') {
            $function = '';
        }
        $file = debug_backtrace()[$count - 2]['file'];
        $line = debug_backtrace()[$count - 2]['line'];
    }
    $details = array(
        'timestamp' => core_timestamp(),
        'type' => $type,
        'message' => $message,
        'level' => $level,
        'file' => $file,
        'function' => $function,
        'line' => $line,
    );
    core_log_commit($details);
}

function core_debug($type = '', $message = '') {
    if (core_config_get('superdebug', FALSE) == TRUE) {
        core_log($type, $message, 'debug');
    }
}

// OWASP recommendations.
function core_cookie_delete($name = '') {
    // ensure the cookie expires in browser.
    setcookie($name, '', 1);
    // the standard way of removing a cookie (you can't store false in a cookie.)
    setcookie($name, FALSE);
    // removes the cookie from the current script execution.
    unset($_COOKIE[$name]);
}

function core_require_file($file = '') {
    if (!file_exists($file)) {
        return FALSE;
    }
    require $file;
}

function core_config_get($name = '', $fallback = '') {
    global $config;
    if (!$value = &core_static(__FUNCTION__ . ':' . $name)) {
        if (isset($config[$name])) {
            $value = $config[$name];
        } else {
            $value = $fallback;
        }
    }
    return $value;
}

function core_bootstrap() {
    global $config;
    $config['project_root'] = dirname(dirname(dirname(dirname(__DIR__))));
    $config['document_root'] = $config['project_root'] . DIRECTORY_SEPARATOR . 'public';
// @TODO this runs into issues on our test environment
    require $config['project_root'] . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

    set_error_handler('core_error_handler');
    register_shutdown_function('core_shutdown_function');

    core_cache_init();

    if (isset($_SERVER['HTTP_HOST'])) {
        core_session_init();
        core_request_init();
        core_user_init();
        core_theme_init();
    }

// @TODO MODULE CONCEPT. TBD. SHOULD BE MORE THAN JUST $modules ARRAY
    require $config['project_root'] . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'modules.php';
    foreach ($modules as $module) {
        core_require_file($config['project_root'] . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $module . '.php');
    }

    if (isset($_SERVER['HTTP_HOST'])) {
// @TODO ROUTE DEFINITION SHOULD BE MORE THAN JUST $routes ARRAY
        core_router_init();
    }
}

function core_shutdown_function() {
    // allows us to capture fatal errors. as long as they're defined in the shutdown function before it happens.
    if ($error = error_get_last()) {
        core_error_handler($error['type'], $error['message'], $error['file'], $error['line']);
        if ($error['type'] === E_ERROR || $error['type'] === E_USER_ERROR) {
// @TODO: throw an official 5xx page?
             header('HTTP/1.0 500 Server Error');
             exit;
        }
    }
}

<?php
function core_backtrace($quick = FALSE) {
    $backtrace = debug_backtrace();
    array_shift($backtrace);
    if ($quick) {
        return array(
            'function' => $backtrace[0]['function'],
            'line' => $backtrace[1]['line'],
            'file' => $backtrace[1]['file'],
        );
    }
    foreach ($backtrace as $item) {
        if (in_array($item['function'], array('core_log_commit', 'core_log'))) {
            continue;
        }
        if (isset($item['args'])) {
            $args = array();
            foreach ($item['args'] as $arg) {
                if (is_object($arg)) {
                    $str = get_class($arg);
                } elseif (is_array($arg)) {
                    $str = 'Array';
                } elseif (is_numeric($arg)) {
                    $str = $arg;
                } else {
                    $str = "'$arg'";
                }
                $args[] = $str;
            }
            $args = implode(', ', $args);
        }
        $return[] = str_replace(core_config_get('project_root') . DIRECTORY_SEPARATOR, '', $item['file']) . ':' . $item['line'] . ' ' . $item['function'] . '(' . (!empty($args) ? $args : '') . ')';;
    }
    return $return;
}

// this function captures both PHP-generated errors and notices, as well as our own core_log() messages.
function core_log_commit($details = array()) {

    if (!$logging = core_config_get('log', FALSE)) {
        return FALSE;
    }

    $details['level'] = core_error_normalize($details['level']);
    $details['url'] = isset($GLOBALS['request']['url']) ? $GLOBALS['request']['url'] : '';
    $details['id'] = isset($GLOBALS['request']['id']) ? $GLOBALS['request']['id'] : '';
    $details['user_id'] = isset($GLOBALS['user']['user_id']) ? $GLOBALS['user']['user_id'] : 0;
    $details['ip'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $details['referrer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] :'';

    if (in_array('display', $logging)) {
        echo '<fieldset style="border: 1px solid ' . core_error_color($details['level']) . '; padding: 10px; margin-bottom: 10px;">';
        echo '<legend>' . ucfirst($details['level']) . ' (module: ' . $details['module'] . ')</legend>';
        echo nl2br(core_format_escape($details['message']));
        if ($details['level'] != 'debug') {
            echo '<br /><br />Stack trace:<ol>';
            $backtrace = core_backtrace();
            foreach ($backtrace as $item) {
                echo '<li>' . $item . '</li>';
            }
            echo '</ol>';
        }
        echo '</fieldset>';
    }

    if (in_array('syslog', $logging)) {
        openlog('php/core', LOG_ODELAY | LOG_PID, LOG_USER);
        syslog(core_error_syslog($details['level']), $details['module'] . ': ' . $details['message']);
    }

    if ($details['level'] != 'debug') {
        $details['message'] .= PHP_EOL . PHP_EOL . 'Stack trace:' . PHP_EOL;
        $backtrace = core_backtrace();
        $i = 1;
        foreach ($backtrace as $item) {
           $details['message'] .= $i . '. ' . $item . PHP_EOL;
           $i++;
        }
    }

    if (in_array('file', $logging)) {
        $dir = core_config_get('project_root') . DIRECTORY_SEPARATOR . 'var';
        if (!is_dir($dir)) {
// @TODO - figure out a cleaner way without having to "@"
            @mkdir($dir, 0711, TRUE);
        }
        $file = $dir . DIRECTORY_SEPARATOR . 'app.log';
// only execute this for specific levels
// @TODO - figure out a cleaner way without having to "@"
        @file_put_contents($file, print_r($details, TRUE), FILE_APPEND);
    }

    if (in_array('database', $logging)) {
// need to check the db is available!
        core_db_query("INSERT INTO core_log (log_timestamp, log_module, log_level, log_file, log_line, log_function, log_request_id, log_url, log_referrer, log_ip, log_user_id, log_message) VALUES (" . core_timestamp() . ", '" . core_db_escape($details['module']). "', '" . core_db_escape($details['level']) . "', '" . core_db_escape($details['file']) . "', '" . intval($details['line']). "', '" . (!empty($details['function']) ? core_db_escape($details['function']) : '') . "', '" . core_db_escape($details['id']) . "', '" . core_db_escape($details['url']) . "', '" . core_db_escape($details['referrer']). "', '" . core_db_escape($details['ip']). "', " . intval($details['user_id']). ", '" . core_db_escape($details['message']). "')");
    }
}

// map our levels to syslog. see http://php.net/manual/en/function.syslog.php
function core_error_syslog($level = '') {
    if ($return = &core_static(__FUNCTION__ . ':' . $level) && $return !== NULL) {
      return $return;
    }
    switch (core_error_normalize($level)) {
        case 'fatal':
            // LOG_ALERT or LOG_EMERG could also be an option
            $return = LOG_CRIT;
            break;
        case 'error':
            $return = LOG_ERR;
            break;
        case 'warning':
            $return = LOG_WARNING;
            break;
        case 'notice':
            $return = LOG_NOTICE;
            break;
        default:
            $return = LOG_DEBUG;
            break;
    }
    return $return;
}

function core_error_color($level = '') {
    if ($return = &core_static(__FUNCTION__ . ':' . $level) && $return !== NULL) {
      return $return;
    }
    switch (core_error_normalize($level)) {
        case 'fatal':
            $return = 'red';
            break;
        case 'error':
            $return = 'red';
            break;
        case 'warning':
            $return = 'orange';
            break;
        case 'notice':
            $return = 'orange';
            break;
        default:
            $return = 'black';
            break;
    }
    return $return;
}

// map PHP constants and other possibles to one of our levels.
function core_error_normalize($level = '') {
    if ($return = &core_static(__FUNCTION__ . ':' . $level) && $return !== NULL) {
      return $return;
    }
    switch ($level) {
        case E_CORE_ERROR:
        case E_ERROR:
        case E_PARSE:
        case 'fatal':
            $return = 'fatal';
            break;
        case E_RECOVERABLE_ERROR:
        case E_USER_ERROR:
        case 'error':
            $return = 'error';
            break;
        case E_USER_WARNING:
        case E_CORE_WARNING:
        case E_WARNING:
        case 'warning':
            $return = 'warning';
            break;
        case E_USER_NOTICE:
        case E_NOTICE:
        case E_STRICT:
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
        case 'notice':
            $return = 'notice';
            break;
        default:
            $return = 'debug';
            break;
    }
    return $return;
}

// to handle PHP errors
function core_error_handler($level = 0, $message = '', $file = '', $line = 0, $context = array()) {
    $backtrace = debug_backtrace();
    $details['timestamp'] = core_timestamp();
    if ($backtrace[0]['args'][4]['error']) {
        $details = array(
            'module' => 'php',
            'message' => $backtrace[0]['args'][4]['error']['message'],
            'level' => core_error_normalize($level),
            'file' => $backtrace[0]['args'][4]['error']['file'],
            'line' => $backtrace[0]['args'][4]['error']['line'],
        );
    } else {
// @TODO: what to do here?
echo "core_error_handler given an error that it does not know how to handle yet";
    }
    core_log_commit($details);
}

// our own central logging function.
function core_log($module = '', $message = '', $level = 'debug') {
    $backtrace = core_backtrace(TRUE);
    $details = array(
        'timestamp' => core_timestamp(),
        'module' => $module,
        'message' => $message,
        'level' => $level,
        'file' => $backtrace['file'],
        'function' => $backtrace['function'],
        'line' => $backtrace['line'],
    );
    core_log_commit($details);
}

function core_debug($module = '', $message = '') {
    if (core_config_get('superdebug', FALSE) == TRUE) {
        core_log($module, $message, 'debug');
    }
}

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
        if ($error['type'] === E_ERROR || $error['type'] === E_USER_ERROR || $error['type'] == E_PARSE) {
            trigger_error($error['message'], $error['type']);
// @TODO: throw an official 5xx page?
            header('HTTP/1.0 500 Server Error');
            exit;
        }
    }
}

<?php
$config['project_root'] = dirname(dirname(dirname(dirname(__DIR__))));
$config['document_root'] = $config['project_root'] . DIRECTORY_SEPARATOR . 'public';

require $config['project_root'] . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
$GLOBALS['config'] = $config;

function core_config_get($name = '', $fallback = '') {
  global $config;
// @TODO - heavy caching
  if (isset($config[$name])) {
    return $config[$name];
  }
  return $fallback;
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
  }
  elseif (!isset($message_levels[$details['level']])) {
    $details['level'] = 'debug';
  }

  $details['url'] = isset($GLOBALS['request']['url']) ? $GLOBALS['request']['url'] : '';
  $details['id'] = isset($GLOBALS['request']['id']) ? $GLOBALS['request']['id'] : '';
  $details['user_id'] = isset($GLOBALS['user']['user_id']) ? $GLOBALS['user']['user_id'] : 0;
  $details['ip'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
  $details['referrer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] :'';

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


// set the default error handler as early as possible.
function core_error_handler($level = 0, $message = '', $file = '', $line = 0, $context = array()) {
  $message = $message . PHP_EOL;
  $message .= 'Stack trace:';
  foreach (debug_backtrace() as $step) {
    $message .= PHP_EOL . $step['file'] . ':' . $step['line'];
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
set_error_handler('core_error_handler');

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
  global $config;
  if (!empty($config['superdebug'])) {
    core_log($type, $message, 'debug');
  }
}

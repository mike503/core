<?php
/*
this file is the single frontend file for all web requests.
*/
if (isset($_GET['xhprof']) && !empty($_SERVER['XHPROF_ROOT']) ) {
  include_once $_SERVER['XHPROF_ROOT'] . '/xhprof_lib/utils/xhprof_lib.php';
  include_once $_SERVER['XHPROF_ROOT']. '/xhprof_lib/utils/xhprof_runs.php';
  xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
}

// bootstraps database, main $config, etc.
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'bootstrap.php';

// required to setup user sessions for web requests.
require $config['application_root'] . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'session.php';

// set some useful reusable variables for web requests.
$url = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$request = parse_url($url);
$request['id'] = preg_replace('/[^0-9]/', '', (isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime())) . substr(md5(rand()), 0, 12);
$request['url'] = $url;
if (isset($request['query'])) {
  $request['params'] = array();
  parse_str($request['query'], $request['params']);
}
$request['parts'] = explode('/', $request['path']);
$request['base'] = core_config_get('base_path', '');
$request['assets'] = core_config_get('asset_path', '');

// trim off trailing slash if necessary.
if (strlen($request['path']) > 1 && substr($request['path'], -1) == '/') {
  header('Location: ' . $request['scheme'] . '://' . $request['host'] . (isset($request['port']) ? ':' . $request['port'] : '') . rtrim($request['path'], '/') . (isset($request['query']) ? '?' . $request['query'] : '') . (isset($request['fragment']) ? $request['fragment'] : ''));
  exit;
}

// SESSION
core_session_init();

// FORM
foreach ($_POST as $key => $val) {
  $_SESSION['state']['form_field_previous'][$key] = $val;
}

// DESTINATION
// @TODO - whitelist check?
if (isset($request['params']['destination'])) {
  $_SESSION['destination'] = $request['params']['destination'];
}

// USER
core_user_init();

// THEME
core_theme_init();

// ROUTER
core_router_init();

require $config['application_root'] . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . $request['route']['file'];

unset($_SESSION['state']);

if (isset($_GET['xhprof']) && !empty($_SERVER['XHPROF_ROOT'])) {
  function _make_xhprof_run_id() {
    if (isset($_SERVER['HTTPS'])) {
      $run_id = 'https-';
    }
    else {
      $run_id = 'http-';
    }
    $run_id .= urldecode($_SERVER['HTTP_HOST'] . '/' . $_SERVER['REQUEST_URI']) . '-' . microtime(TRUE);
    $run_id = trim(preg_replace('|([^A-Za-z0-9])|', '-', $run_id), '-');
    while (strstr($run_id, '--')) {
      $run_id = str_replace('--' , '-', $run_id);
    }
    return $run_id;
  }
  $xhprof_data = xhprof_disable();
  $xhprof_runs = new XHProfRuns_Default();
  $run_id = $xhprof_runs->save_run($xhprof_data, 'xhprof_testing', _make_xhprof_run_id());
  if (!empty($_SERVER['XHPROF_URI']) ) {
    $uri = "{$_SERVER['XHPROF_URI']}/index.php?run={$run_id}&source=xhprof_testing";
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
      $uri = "http://{$_SERVER['HTTP_HOST']}" . $uri;
    }
    else {
      $uri = "https://{$_SERVER['HTTP_HOST']}" . $uri;
    }
    echo "<div>XHPROF: <a href=\"$uri\" target=\"blank\">$uri</a></div>";
    core_log('xhprof', 'request profiled: ' . $uri, 'notice');
  }
}

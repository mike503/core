<?php
if (isset($_GET['xhprof']) && !empty($_SERVER['XHPROF_ROOT']) ) {
  include_once $_SERVER['XHPROF_ROOT'] . '/xhprof_lib/utils/xhprof_lib.php';
  include_once $_SERVER['XHPROF_ROOT']. '/xhprof_lib/utils/xhprof_runs.php';
  xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

core_session_init();
core_request_init();
core_session_init();
core_user_init();
core_theme_init();
core_router_init();

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

<?php
function core_router_init() {
  if ($cached = core_cache_get('router', 'route:' . $GLOBALS['request']['path'])) {
    $GLOBALS['request']['route'] = $cached;
  }
  else {
    if (core_router_regenerate()) {
      require $GLOBALS['config']['project_root'] . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes.cached.php';
    }
    else {
      $routes = array();
    }
    $GLOBALS['request']['route'] = array(
      'file' => '404.php',
      'cached' => 0,
    );
    // 10 seconds by default.
    $expiry = 10;
    foreach ($routes as $pattern => $file) {
      if (preg_match('|' . $pattern . '|', $GLOBALS['request']['path'], $args)) {
        if (file_exists($GLOBALS['config']['project_root'] . DIRECTORY_SEPARATOR . 'handlers' . DIRECTORY_SEPARATOR . $file)) {
          $GLOBALS['request']['route'] = array(
            'file' => $file,
            'arguments' => $args,
            'cached' => core_timestamp(),
           );
          // 1 hour cache for a hit.
          $expiry = 3600;
        }
        break;
      }
    }
    core_cache_set('router', 'route:' . $GLOBALS['request']['path'], $GLOBALS['request']['route'], $expiry);
  }
  if (core_config_get('superdebug', FALSE)) {
    core_log('router', 'request: "' . $GLOBALS['request']['path'] . '" file: "' . $GLOBALS['request']['route']['file'] . '" cached: "' . ($GLOBALS['request']['route']['cached'] > 0 ? core_format_duration($GLOBALS['request']['route']['cached']) : 'n/a') . '"');
  }
  $handler = $GLOBALS['config']['project_root'] . DIRECTORY_SEPARATOR . 'handlers' . DIRECTORY_SEPARATOR . $request['route']['file'];
  if (file_exists($handler)) {
    require $handler;
  }
  else {
// TODO - throw 4xx or 5xx?
    core_log('router', 'handler file does not exist: ' . $handler, 'fatal');
  }
  unset($_SESSION['state']);
}

function core_router_regenerate($force = FALSE) {
  $route_file = $GLOBALS['config']['project_root'] . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes.php';
  if (!file_exists($route_file)) {
// TODO - throw 4xx or 5xx?
    core_log('router', 'route definiition file does not exist!', 'fatal');
    return FALSE;
  }
  $cache_file = $GLOBALS['config']['project_root'] . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes.cached.php';
  if (!file_exists($cache_file) || filemtime($route_file) > filemtime($cache_file) || $force) {
    core_log('router', 'regenerating route cache file', 'info');
    require $route_file;
    function _route_compare($a, $b) {
      if (strlen($a) > strlen($b)) {
        return $a;
      }
    }
    uksort($routes, '_route_compare');
    if (!file_put_contents($cache_file, '<?php $routes = ' . var_export($routes, TRUE) . ';')) {
      core_log('router', 'failed to write route cache file', 'error');
      return FALSE;
    }
    core_cache_flush('router');
  }
  return TRUE;
}

function core_router_argument($argument = 0) {
  if (isset($GLOBALS['request']['route']['arguments'][$argument])) {
    return $GLOBALS['request']['route']['arguments'][$argument];
  }
  return FALSE;
}

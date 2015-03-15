<?php
function core_router_init() {
  global $config, $request;
  if ($cached = core_cache_get('router', 'route:' . $request['path'])) {
    $request['route'] = $cached;
  }
  else {
    if (core_router_regenerate()) {
      require $config['project_root'] . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes.cached.php';
    }
    else {
      $routes = array();
    }
    $request['route'] = array(
      'file' => '404.php',
      'cached' => 0,
    );
    // 10 seconds by default.
    $expiry = 10;
    foreach ($routes as $pattern => $file) {
      if (preg_match('|' . $pattern . '|', $request['path'], $args)) {
        if (file_exists($config['project_root'] . DIRECTORY_SEPARATOR . 'handlers' . DIRECTORY_SEPARATOR . $file)) {
          $request['route'] = array(
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
    core_cache_set('router', 'route:' . $request['path'], $request['route'], $expiry);
  }
  if (core_config_get('superdebug', FALSE)) {
    core_log('router', 'request: "' . $request['path'] . '" file: "' . $request['route']['file'] . '" cached: "' . ($request['route']['cached'] > 0 ? core_format_duration($request['route']['cached']) : 'n/a') . '"');
  }
  if (!empty($request['route']['file'])) {
    if ($request['route']['file'] == '404.php' && !file_exists($config['project_root'] . DIRECTORY_SEPARATOR . 'handlers' . DIRECTORY_SEPARATOR . $request['route']['file'])) {
      header('HTTP/1.0 404 Not Found');
      exit;
    }
    else {
      require $config['project_root'] . DIRECTORY_SEPARATOR . 'handlers' . DIRECTORY_SEPARATOR . $request['route']['file'];
    }
  }
  else {
// TODO - throw 4xx or 5xx?
    core_log('router', 'handler file does not exist: ' . $handler, 'fatal');
  }
  unset($_SESSION['state']);
}

function core_router_regenerate($force = FALSE) {
  global $config, $request;
  $route_file = $config['project_root'] . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes.php';
  if (!file_exists($route_file)) {
// TODO - throw 4xx or 5xx?
    core_log('router', 'route definiition file does not exist!', 'fatal');
    return FALSE;
  }
  $cache_file = $config['project_root'] . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes.cached.php';
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
  global $request;
  if (isset($request['route']['arguments'][$argument])) {
    return $request['route']['arguments'][$argument];
  }
  return FALSE;
}

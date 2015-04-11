<?php
function core_router_init() {
    global $request;
    if ($cached = core_cache_get('router', $request['path'])) {
        $request['route'] = $cached;
    } else {
        if (core_router_regenerate()) {
            require core_config_get('project_root') . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'routes.cached.php';
        } else {
            $routes = array();
        }
        // 10 seconds by default.
        $expiry = 10;
        foreach ($routes as $pattern => $handler) {
// @TODO - $handler becomes 'function' and 'file'
            if (preg_match('|' . str_replace('|', '\\|', $pattern) . '|', $request['path'], $arguments)) {
               unset($arguments[0]);
               $request['route'] = array(
                    'handler' => $handler,
                    'arguments' => $arguments,
                    'cached' => core_timestamp(),
                );
                // 1 hour cache for a hit.
                $expiry = 3600;
                break;
            }
        }
        core_cache_set('router', $request['path'], $request['route'], $expiry);
    }
    if (core_config_get('superdebug', FALSE)) {
        core_log('router', 'request: "' . $request['path'] . '" handler: "' . $request['route']['handler'] . '" cached: "' . ($request['route']['cached'] > 0 ? core_format_duration($request['route']['cached']) : 'n/a') . '"');
    }
    if (!isset($request['route']['handler'])) {
        core_not_found();
    }
    $handler = core_config_get('project_root') . DIRECTORY_SEPARATOR . 'handlers' . DIRECTORY_SEPARATOR . $request['route']['handler'];
    if (!file_exists($handler)) {
        core_log('router', 'handler file does not exist: ' . $handler, 'fatal');
        core_not_found();
    }
    require $handler;
    unset($_SESSION['state']);
}

function core_router_regenerate($force = FALSE) {
    $route_file = core_config_get('project_root') . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes.php';
    if (!file_exists($route_file)) {
// TODO - throw 4xx or 5xx?
        core_log('router', 'route definition file does not exist: ' . $route_file, 'fatal');
        return FALSE;
    }
    $cache_file = core_config_get('project_root') . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'routes.cached.php';
    if (!is_dir(dirname($cache_file))) {
        if (!mkdir(dirname($cache_file), 0711, TRUE)) {
            core_log('router', 'failed to create route cache directory: ' . dirname($cache_file), 'error');
            return FALSE;
        }
    }
    if (!file_exists($cache_file) || filemtime($route_file) > filemtime($cache_file) || $force) {
        core_log('router', 'regenerating route cache file', 'info');
        require $route_file;
        function _route_strip($string = '') {
            $string = preg_replace('/\(\\\(\S)(\+|)\)/', '', $string);
            $string = preg_replace('/[^0-9A-Za-z\/]/', '', $string);
            return $string;
        }
        function _route_compare($a = '', $b = '') {
            if (strlen(_route_strip($a)) > strlen(_route_strip($b))) {
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

function core_router_argument($position = 0, $fallback = '') {
    if ($return = &core_static(__FUNCTION__ . ':' . $position) && $return !== NULL) {
        return $return;
    }
    global $request;
    if (isset($request['route']['arguments'][$position])) {
        $return = $request['route']['arguments'][$position];
    } else {
        $return = $fallback;
    }
    return $return;
}

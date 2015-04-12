<?php
function core_router_init() {
    if ($cached = core_cache_get('router', core_registry_get('request.path'))) {
        core_registry_set('request.route', $cached);
    } else {
        if (core_router_regenerate()) {
            require core_registry_get('config.project_root') . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'routes.cached.php';
        } else {
            $routes = array();
        }
        // 10 seconds by default.
        $expiry = 10;
        foreach ($routes as $pattern => $handler) {
// @TODO - $handler becomes 'function' and 'file'
            if (preg_match('|' . str_replace('|', '\\|', $pattern) . '|', core_registry_get('request.path'), $arguments)) {
                unset($arguments[0]);
                core_registry_set('request.route', array(
                    'handler' => $handler,
                    'arguments' => $arguments,
                    'cached' => core_registry_get('core.now'),
                ));
                // 1 hour cache for a hit.
                $expiry = 3600;
                core_cache_set('router', core_registry_get('request.path'), core_registry_get('request.route'), $expiry);
                break;
            }
        }
    }
    $handler = core_registry_get('request.route')['handler'];
    if (core_registry_get('config.superdebug', FALSE)) {
        core_log('router', 'request: "' . core_registry_get('request.path') . '" handler: "' . $handler . '" cached: "' . (core_registry_get('request.route.cached') > 0 ? core_format_duration(core_registry_get('request.route.cached')) : 'n/a') . '"');
    }
    if (empty($handler)) {
// @TODO - cache not founds for a short period?
        core_not_found();
    }
    $handler = core_registry_get('config.project_root') . DIRECTORY_SEPARATOR . 'handlers' . DIRECTORY_SEPARATOR . $handler;
    if (!file_exists($handler)) {
        core_log('router', 'handler file does not exist: ' . $handler, 'fatal');
// @TODO - cache not founds for a short period?
        core_not_found();
    }
    require $handler;
// would SESSION be part of registry...?
    unset($_SESSION['state']);
}

function core_router_regenerate($force = FALSE) {
    $route_file = core_registry_get('config.project_root') . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes.php';
    if (!file_exists($route_file)) {
// TODO - throw 4xx or 5xx?
        core_log('router', 'route definition file does not exist: ' . $route_file, 'fatal');
        return FALSE;
    }
    $cache_file = core_registry_get('config.project_root') . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'routes.cached.php';
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

<?php
function core_request_init() {
    global $request;

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
}

function core_request_get($name = '', $fallback = '') {
    global $request;
    if (!$value = &core_static(__FUNCTION__ . $name . $fallback)) {
        if (isset($request[$name])) {
            $value = $request[$name];
        } else {
            $value = $fallback;
        }
    }
    return $value;
}

function core_request_argument($position = 0, $fallback = '') {
    global $request;
    if (!$value = &core_static(__FUNCTION__ . $position . $fallback)) {
        if (isset($request['arguments'][$position])) {
            $value = $request['arguments'][$position];
        } else {
            $value = $fallback;
        }
    }
    return $value;
}

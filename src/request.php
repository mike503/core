<?php
function core_request_init() {
    $url = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $request = parse_url($url);
    core_registry_set('request.id', preg_replace('/[^0-9]/', '', (isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime())) . substr(md5(rand()), 0, 12));
    core_registry_set('request.url', $url);
    foreach ($request as $key => $value) {
// might skip some like $key = 'query'
        core_registry_set('request.' . $key, $value);
    }
    core_registry_set('request.method', $_SERVER['REQUEST_METHOD']);
    if (isset($request['query'])) {
        $params = array();
        parse_str($request['query'], $params);
        if (!empty($params)) {
// @TODO - prevent DDOS from too many query strings or long ones due to abuse...
            core_registry_set('request.query', $params);
        }
    }

    // path parts.
    $parts = explode('/', substr($request['path'], strlen(core_registry_get('config.base_path', '/'))));
    unset($parts[0]);
    core_registry_set('request.path.parts', $parts);

    // this is a "fact" for this request.
    core_registry_set('request.base', core_registry_get('config.base_path', '/'));

    // trim off trailing slash if necessary.
    if (strlen($request['path']) > 1 && substr($request['path'], -1) == '/') {
        header('Location: ' . $request['scheme'] . '://' . $request['host'] . (isset($request['port']) ? ':' . $request['port'] : '') . rtrim($request['path'], '/') . (isset($request['query']) ? '?' . $request['query'] : '') . (isset($request['fragment']) ? $request['fragment'] : ''));
        exit;
    }
}

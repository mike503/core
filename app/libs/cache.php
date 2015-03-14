<?php
$file = __DIR__ . DIRECTORY_SEPARATOR . 'cache.' . $config['cache'] . '.php';
if (!file_exists($file)) {
  core_log('cache', 'failed to load cache module file: ' . $file);
}
else {
  require $file;
}

if (!function_exists('core_cache_flush')) {
  function core_cache_flush() {
    return FALSE;
  }
}

if (!function_exists('core_cache_get')) {
  function core_cache_get() {
    return FALSE;
  }
}

if (!function_exists('core_cache_set')) {
  function core_cache_set() {
    return FALSE;
  }
}

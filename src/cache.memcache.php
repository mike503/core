<?php
function core_cache_check() {
  if (isset($GLOBALS['ch'])) {
    return TRUE;
  }
  elseif (core_cache_open()) {
    return TRUE;
  }
  return FALSE;
}

function core_cache_del($bucket = '', $key = '') {
  if (core_cache_check()) {
    core_debug('cache', 'delete called for bucket: "' . $bucket . '" key: "' . $key . '"');
    return memcache_delete($GLOBALS['ch'], $bucket . ':' . $GLOBALS['config']['cache_prefix'] . ':' . $key);
  }
}

// $bucket has no real usage here right now.
function core_cache_flush($bucket = '') {
  if (core_cache_check()) {
    memcache_flush($GLOBALS['ch']);
  }
}

function core_cache_get($bucket = '', $key = '') {
  $return = FALSE;
  if (core_cache_check()) {
    if (!$return = memcache_get($GLOBALS['ch'], $bucket . ':' . $GLOBALS['config']['memcache_key_prefix'] . ':' . $key)) {
      core_debug('cache', 'get MISS for bucket: "' . $bucket . '" key: "' . $key . '"');
    }
    else {
      core_debug('cache', 'get HIT for bucket: "' . $bucket . '" key: "' . $key . '"');
    }
  }
  return $return;
}

function core_cache_open() {
  $return = FALSE;
  if (!isset($GLOBALS['config']['cache']) || !$GLOBALS['config']['cache']) {
    core_log('cache', 'cache functions are being called, but are not enabled.', 'notice');
    return FALSE;
  }
  if (!extension_loaded('memcache')) {
    core_log('cache', 'memcache PHP extension is not loaded.', 'error');
    return FALSE;
  }
  if (!isset($GLOBALS['config']['memcache_servers']) || !is_array($GLOBALS['config']['memcache_servers'])) {
    core_log('cache', 'memcache server list is not defined or empty.', 'error');
    return FALSE;
  }
// @TODO - i don't like how this is written.
  foreach ($GLOBALS['config']['memcache_servers'] as $key => $value) {
    if ($key == 0) {
      if ($GLOBALS['ch'] = @memcache_pconnect($value, 11211)) {
        $return = TRUE;
      }
    }
    else {
      @memcache_add_server($GLOBALS['ch'], $value, 11211);
    }
  }
  return $return;
}

function core_cache_set($bucket = '', $key = '', $value = '', $ttl = 2592000) {
  if (core_cache_check()) {
    core_debug('cache', 'set called for bucket: "' . $bucket . '" key: "' . $key . '" ttl: "' . $ttl . '"');
    return memcache_set($GLOBALS['ch'], $bucket . ':' . $GLOBALS['config']['memcache_key_prefix'] . ':' . $key, $value, 0, $ttl);
  }
  return TRUE;
}

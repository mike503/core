<?php
function core_cache_init() {
// @TODO - could be cache.type?
    $cache = core_registry_get('config.cache', '');
    if (empty($cache)) {
        core_log('cache', 'cache module not defined', 'error');
        return FALSE;
    }
    $file = __DIR__ . DIRECTORY_SEPARATOR . 'cache.' . $cache . '.php';
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
}

// ref: https://api.drupal.org/api/drupal/includes%21bootstrap.inc/function/drupal_static/7
function &core_static($name = '', $fallback = NULL, $reset = FALSE) {
  static $data = array(), $default = array();
  // First check if dealing with a previously defined static variable.
  if (isset($data[$name]) || array_key_exists($name, $data)) {
    // Non-NULL $name and both $data[$name] and $default[$name] statics exist.
    if ($reset) {
      // Reset pre-existing static variable to its default value.
      $data[$name] = $default[$name];
    }
    return $data[$name];
  }
  // Neither $data[$name] nor $default[$name] static variables exist.
  if (isset($name)) {
    if ($reset) {
      // Reset was called before a default is set and yet a variable must be
      // returned.
      return $data;
    }
    // First call with new non-NULL $name. Initialize a new static variable.
    $default[$name] = $data[$name] = $fallback;
    return $data[$name];
  }
  // Reset all: ($name == NULL). This needs to be done one at a time so that
  // references returned by earlier invocations of drupal_static() also get
  // reset.
  foreach ($default as $name => $value) {
    $data[$name] = $value;
  }
  // As the function returns a reference, the return should always be a
  // variable.
  return $data;
}

// ref: https://api.drupal.org/api/drupal/includes%21bootstrap.inc/function/drupal_static_reset/7
function core_static_reset($name = NULL) {
    core_static($name, NULL, TRUE);
}

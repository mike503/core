<?php
function core_theme_init() {
  $file = $GLOBALS['config']['application_root'] . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $GLOBALS['config']['site_theme'] . DIRECTORY_SEPARATOR . 'functions.php';
  if (!file_exists($file)) {
    return FALSE;
  }
  require $file;
}

function core_theme_load($name = '') {
  $file = $GLOBALS['config']['application_root'] . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $GLOBALS['config']['site_theme'] . DIRECTORY_SEPARATOR . $name . '.php';
  if (!file_exists($file)) {
    core_log('theme', 'failed to load file: ' . $file, 'error');
    return FALSE;
  }
  require $file;
}

function core_theme_messages($type = '') {
  $types = array('error', 'notice', 'success');
  if (empty($type)) {
    foreach ($types as $t) {
      $function = 'theme_' . $GLOBALS['config']['site_theme'] . '_messages';
      $function($t);
    }
  }
  elseif (in_array($types, $type)) {
    $function = 'theme_' . $GLOBALS['config']['site_theme'] . '_messages';
    $function($type);
  }
}

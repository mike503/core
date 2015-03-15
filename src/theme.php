<?php
function core_theme_init() {
    global $config;
    $file = $config['project_root'] . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $config['site_theme'] . DIRECTORY_SEPARATOR . 'functions.php';
    if (!file_exists($file)) {
        return FALSE;
    }
    require $file;
}

function core_theme_load($name = '') {
    global $config;
    $file = $config['project_root'] . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $config['site_theme'] . DIRECTORY_SEPARATOR . $name . '.php';
    if (!file_exists($file)) {
        core_log('theme', 'failed to load file: ' . $file, 'error');
        return FALSE;
    }
    require $file;
}

function core_theme_messages($type = '') {
    global $config;
    $types = array('error', 'notice', 'success');
    if (empty($type)) {
        foreach ($types as $t) {
            $function = 'theme_' . $config['site_theme'] . '_messages';
            $function($t);
        }
    }
    elseif (in_array($types, $type)) {
        $function = 'theme_' . $config['site_theme'] . '_messages';
        $function($type);
    }
}

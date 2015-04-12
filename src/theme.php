<?php
function core_theme_init() {
    $file = core_registry_get('config.project_root') . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . core_registry_get('config.site_theme', 'default') . DIRECTORY_SEPARATOR . 'functions.php';
    if (!file_exists($file)) {
        return FALSE;
    }
    require $file;
}

function core_theme_load($name = '') {
    $file = core_registry_get('config.project_root') . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . core_registry_get('config.site_theme', 'default') . DIRECTORY_SEPARATOR . $name . '.php';
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
            $function = 'theme_' . core_registry_get('config.site_theme', 'default') . '_messages';
            $function($t);
        }
    }
    elseif (in_array($types, $type)) {
        $function = 'theme_' . core_registry_get('config.site_theme', 'default') . '_messages';
        $function($type);
    }
}

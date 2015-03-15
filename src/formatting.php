<?php
function core_format_escape($string = '') {
    // OWASP recommendation.
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML401, 'UTF-8');
}

function core_format_duration($since = 0) {
    $seconds = core_timestamp() - $since;
    if ($seconds < 1) {
        return '< 1s ago';
    }
    $condition = array(
        12 * 30 * 24 * 60 * 60 => 'year',
        30 * 24 * 60 * 60 => 'month',
        24 * 60 * 60 => 'day',
        60 * 60 => 'hour',
        60 => 'min',
        1 => 'sec',
    );
    foreach ($condition as $secs => $str) {
        $d = $seconds / $secs;
        if ($d >= 1) {
            $r = round($d);
            return $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago';
        }
    }
}

/*
core_format_date()
core_format_datetime()
core_format_address() // MAYBE
*/

function format_bytes($bytes = 0) {
    $i = 0;
    $iec = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    while (($bytes/1024) > 1) {
        $bytes = $bytes/1024;
        $i++;
    }
    return substr($bytes, 0, strpos($bytes, '.') + 3) . $iec[$i];
}

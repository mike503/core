<?php
function core_db_affected() {
  if (core_db_check()) {
    return mysqli_affected_rows($GLOBALS['db']);
  }
  return FALSE;
}

function core_db_check() {
  if (isset($GLOBALS['db'])) {
    return TRUE;
  }
  elseif (core_db_open()) {
    return TRUE;
  }
  return FALSE;
}

function core_db_error($message = '', $query = '') {
  core_log('database', 'error message: "' . $message . '" query: "' . $query . '"', 'error');
}

function core_db_escape($string = '') {
  if (core_db_check()) {
    return mysqli_real_escape_string($GLOBALS['db'], $string);
  }
  // @TODO - find a way to do this "good enough" without calling a legacy function.
  return mysql_escape_string($string);
}

function core_db_free($results) {
  if (isset($results) && is_object($results)) {
    mysqli_free_result($results);
  }
  return FALSE;
}

function core_db_last() {
  if (core_db_check()) {
    return mysqli_insert_id($GLOBALS['db']);
  }
  return FALSE;
}

function core_db_numrows($results) {
  if (isset($results) && is_object($results)) {
    return mysqli_num_rows($results);
  }
  return FALSE;
}

function core_db_open() {
  if (!isset($GLOBALS['config']['database']) || !is_array($GLOBALS['config']['database'])) {
    core_log('database', 'could not connect to the database. invalid database configuration information array.', 'fatal');
    exit;
  }
  if (!function_exists('mysqli_connect')) {
    core_log('ERROR: could not connect to the database. the MySQL module for PHP is not installed.', 'fatal');
    exit;
  }
  // TODO - see if I can try/catch, or anything here to remove '@'
  if (!$db = @mysqli_connect($GLOBALS['config']['database']['hostname'], $GLOBALS['config']['database']['username'], $GLOBALS['config']['database']['password'], $GLOBALS['config']['database']['database'], $GLOBALS['config']['database']['port'], $GLOBALS['config']['database']['socket'])) {
    core_log('ERROR: could not connect to the database. connection error (invalid hostname, socket, username, password, etc.)', 'fatal');
    exit;
  }
  mysqli_set_charset($db, 'utf8');
  $GLOBALS['db'] = $db;
  return TRUE;
}

function core_db_paginate($query = '', $current_page = 1, $items_per_page = 15) {
  if (core_db_check()) {
    $start = $items_per_page * ($current_page - 1);
    if ($results = core_db_query(preg_replace('/^SELECT/i', 'SELECT SQL_CALC_FOUND_ROWS', $query) . " LIMIT $start, {$items_per_page}")) {
      $q = core_db_query('SELECT FOUND_ROWS()');
      list($total_items) = core_db_rows($q);
      core_db_free($q);
      $end = $start + core_db_numrows($results);
      $total_pages = ceil($total_items / $items_per_page);
      $return = array(
        'results' => $results,
        'total_items' => $total_items,
        'total_pages' => $total_pages,
        'start_item' => $start + 1,
        'end_item' => $end,
      );
      return $return;
    }
  }
  return FALSE;
}
 
function core_db_query($query = '') {
  if (core_db_check() && !empty($query)) {
    if (!$results = mysqli_query($GLOBALS['db'], $query)) {
      core_db_error(mysqli_error($GLOBALS['db']), $query);
      return FALSE;
    }
    return $results;
  }
  return FALSE;
}

function core_db_rows($results) {
  if (isset($results) && is_object($results)) {
    return mysqli_fetch_row($results);
  }
  return FALSE;
}

function core_db_rows_assoc($results) {
  if (isset($results) && is_object($results)) {
    return mysqli_fetch_assoc($results);
  }
  return FALSE;
}
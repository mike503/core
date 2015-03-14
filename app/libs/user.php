<?php
function core_user_init() {
  if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    $GLOBALS['user'] = core_user_get($_SESSION['user_id']);
  }
  $GLOBALS['user'] = isset($GLOBALS['user']) && !empty($GLOBALS['user']) ? $GLOBALS['user'] : core_user_skeleton();
}

function core_user_skeleton() {
  return array(
    'user_id' => 0,
    'user_email' => 'anonymous',
    'user_level' => '',
  );
}

function core_user_logged_in() {
  return $GLOBALS['user']['user_id'] > 0 ? TRUE : FALSE;
}

function core_user_logout($destination = '') {
  if (core_user_logged_in()) {
    core_log('user', 'logout called for email: "' . $GLOBALS['user']['user_email'] . '" user_id: "' . $GLOBALS['user']['user_id'] . '"');
    $_SESSION['user_id'] = 0;
    session_destroy();
  }
  header('Location: ' . core_destination($destination));
  exit;
}

function core_user_login($email = '', $password = '') {
  if (!$user = core_user_authenticate($email, $password)) {
    core_log('user', 'login failed for email: "' . $email . '"');
    $_SESSION['state']['error'][] = 'Email address and/or password were incorrect. <a href="/forgot">Forgot password?</a>';
    core_message_set('error', 'Email address and/or password were incorrect. <a href="/forgot">Forgot password?</a>');
    header('Location: ' . $GLOBALS['request']['base'] . '/login');
    exit;
  }
  core_db_query("UPDATE core_user SET user_login_timestamp=" . core_timestamp() . " WHERE user_id=" . $user['user_id']);
  core_log('user', 'login successful for email: "' . $email . '" user_id: "' . $user['user_id'] . '"');
  $_SESSION['user_id'] = $user['user_id'];
  header('Location: ' . core_destination());
  exit;
}

function core_user_authenticate($email = '', $password = '') {
  if ($user = core_user_get_by_email($email)) {
    if (password_verify($password, $user['user_password'])) {
      return $user;
    }
  }
  return FALSE;
}

function core_user_get($user_id = 0) {
// @TODO - caching
  $q = core_db_query("SELECT * FROM core_user WHERE user_id=" . intval($user_id));
  if (core_db_numrows($q) == 1) {
    return core_db_rows_assoc($q);
  }
  core_db_free($q);
  return FALSE;
}

function core_user_get_by_email($email = '') {
  $q = core_db_query("SELECT user_id FROM core_user WHERE user_email='" . core_db_escape($email) . "'"); 
  if (core_db_numrows($q) == 1) {
    return core_user_get(core_db_rows($q)[0]);
  }
  return FALSE;
}

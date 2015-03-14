<?php
// @NOTE - currently does not support attachments.
// @NOTE - $headers['to'] can accept "Some User <foo@bar.com>" and also multiple recipients.
// @TODO - additional headers we could look at using: "Reply-To:" "Content-type:" "MIME-Version:"
// @TODO - might want to reformat body to make sure all line endings are CRLF (\r\n)
// @TODO - do I like to use the "-f" at the end? should it also be the "From"
function core_email($type = '', $headers = array(), $tokens = array()) {

  if (!isset($headers['to']) || !core_validate_email($headers['to'])) {
    core_log('mail', 'cannot send email to an invalid address', 'error');
    return FALSE;
  }
  if (!isset($headers['subject']) || empty($headers['subject'])) {
    core_log('mail', 'cannot send email to an invalid address', 'error');
    return FALSE;
  }

  // define a unique mail ID for tracing this back.
  $mail_id = date('Ymd') . substr(md5($headers['to'] . $GLOBALS['config']['salt']) . md5(time() . $GLOBALS['config']['salt']), 0, 40);

  $headers['from'] = isset($headers['from']) ? $headers['from'] : $GLOBALS['config']['site_email'];
  $additional_headers = 'From: ' . $headers['from'] . "\r\n";
  $additional_headers .= 'X-Mail-ID: ' . $mail_id . "\r\n";
  if (isset($headers['cc'])) {
    $additional_headers .= 'Cc: ' . $headers['cc'] . "\r\n";
  }
  if (isset($headers['bcc'])) {
    $additional_headers .= 'Bcc: ' . $headers['bcc'] . "\r\n";
  }

// @TODO - have not tested this since the remap
  $template = $GLOBALS['config']['theme_root'] . DIRECTORY_SEPARATOR . 'emails' . DIRECTORY_SEPARATOR . $type . '.txt';
  if (!file_exists($template)) {
    core_log('mail', 'email template missing: ' . $template, 'error');
    return FALSE;
  }

  // body replacement.
  $body = file_get_contents($template);
  foreach ($tokens as $key => $value) {
    $body = str_replace('%%' . $key . '%%', $value, $body);
  }
  $body .= PHP_EOL;
  $body .= 'Mail ID: ' . $mail_id . PHP_EOL;

  core_log('mail', 'mail-id: ' . $mail_id . ' headers: ' . print_r($headers, TRUE), 'info');
  if (!mail($headers['to'], $headers['subject'], $body, $additional_headers, '-f' . $GLOBALS['config']['site_email'])) {
    core_log('mail', 'mail-id: ' . $mail_id . ' failed to send', 'error');
    return FALSE;
  }
  return TRUE;
}

function core_form_previous($name = '', $encode = TRUE) {
  $return = '';
  // check the session first - previously attempted submission.
  if (isset($_SESSION['state']['form_field_previous'][$name])) {
    $return = $_SESSION['state']['form_field_previous'][$name];
  }
  // next check the globals, no previous form submission. populated by the database, probably.
  elseif (isset($GLOBALS['form_field_previous'][$name])) {
    $return = $GLOBALS['form_field_previous'][$name];
  }
  return $encode ? htmlspecialchars($return) : $return;
}

function core_message_set($type = '', $message = '') {
  // add to session message stack
  $_SESSION['state']['messages'][$type][] = $message;
}

function core_message_get($type = '') {
  $return = '';
  if (!empty($type) && isset($_SESSION['state']['messages'][$type]) && is_array($_SESSION['state']['messages'][$type])) {
    $return = $_SESSION['state']['messages'][$type];
    unset($_SESSION['state']['messages'][$type]);
  }
  elseif (isset($_SESSION['state']['messages'])) {
    $return = $_SESSION['state']['messages'];
  }
  unset($_SESSION['state']['messages']);
  return $return;
}

function core_destination($url = '') {
  if (empty($url)) {
    if (isset($GLOBALS['request']['params']['destination'])) {
      $url = $GLOBALS['request']['params']['destination'];
    }
    elseif (isset($_SESSION['destination'])) {
      $url = $_SESSION['destination'];
      unset($_SESSION['destination']);
    }
    else {
      $url = !empty($GLOBALS['request']['base']) ? $GLOBALS['request']['base'] : '/';
    }
  }
  return $url;
}

// should go in theme layer, probably
function core_form_highlight($name = '') {
  if (isset($_SESSION['state']['form_field_error'][$name])) {
// @TODO - useless with bootstrap. http://getbootstrap.com/css/#forms-control-validation
    unset($_SESSION['state']['form_field_error'][$name]);
  }
}

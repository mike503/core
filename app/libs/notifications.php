<?php
/*
 * details is sort of a mail form style inject into a template loaded by type
 * user also has preferences based on type
 */
function core_notify($user_id = 0, $type = '', $details = array()) {
}

/*
 * Maybe add from or from display
 * $extra has extra headers, attachments
 */
function core_notify_email($email = '', $subject = '', $body = '', $extra = array ()) {
}

function core_notify_sms($user_id = '', $message = '') {
}

// maybe/maybe not
function core_notify_popup() {
}

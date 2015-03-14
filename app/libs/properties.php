<?php
// @TODO - caching
function property_access($property_id = 0, $user_id = 0) {
  if (isset($GLOBALS['user']) && $GLOBALS['user']['user_level'] == 'ADMIN') {
    return 'OWNER';
  }
  $q = core_db_query("SELECT user_type FROM property_access WHERE property_id=" . intval($property_id) . " AND user_id=" . intval($user_id) . " AND CURDATE() BETWEEN user_type_begin AND user_type_end");
  if (core_db_numrows($q) == 1) {
    list ($user_type) = core_db_rows($q);
    return $user_type;
  }
  core_db_free($q);
  return FALSE;
}

function property_get($property_id = 0) {
// @TODO - caching
  $q = core_db_query("SELECT * FROM property WHERE property_id=" . intval($property_id));
  if (core_db_numrows($q) == 1) {
    return core_db_rows_assoc($q);
  }
  core_db_free($q);
  return FALSE;
}

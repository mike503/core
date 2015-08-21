STRUCTURE IDEAS:

module, plugin
theme, templates

providers
- cache - only one
- database - multiple
- filesystem - multiple
- email
- notifications (email is part of that...?)
- http transport - curl, socket, guzzle - multiple
- session - file, db, cache? - only one



~~~

external modules:
- figure out how to add more $routes
- how to define, load, etc.

PSR-2:
- https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
- control structures (mainly else) are not on same line
- 4 spaces, not 2

change _curl / _fetch / whatever our request function is to follow HAR specs:
http://www.softwareishard.com/blog/har-12-spec/#request

OWASP ideas:

try to switch to prepared statements:
http://php.net/manual/en/mysqli.quickstart.prepared-statements.php
core_db_query($query = '', [ $params = array() ])



However, the type is not determined by using heuristics that validate it, but by simply reading the data sent by the HTTP request, which is created by a client. A better, yet not perfect, way of validating file types is to use finfo class.
$finfo = new finfo(FILEINFO_MIME_TYPE);
$fileContents = file_get_contents($_FILES['some_name']['tmp_name']);
$mimeType = $finfo->buffer($fileContents);


Session Fixation
Invalidate the Session id after user login (or even after each request) with session_regenerate_id().


You can also use session_name() to retrieve the name default PHP session cookie.

make sure session defines this stuff
 session.name            = myPHPSESSID
 session.hash_function   = 1
 session.hash_bits_per_character = 6
 session.use_trans_sid   = 0
 session.cookie_domain   = full.qualified.domain.name
 #session.cookie_path     = /application/path/
 session.cookie_lifetime = 0
 session.cookie_secure   = On
 session.cookie_httponly = 1
 session.use_only_cookies= 1
 session.cache_expire    = 30



make sure this is setup, too
 expose_php              = Off
 error_reporting         = E_ALL
 display_errors          = Off
 display_startup_errors  = Off
 log_errors              = On
 error_log               = /valid_path/PHP-logs/php_error.log
 ignore_repeated_errors  = Off

iconv.input_encoding = utf-8
iconv.internal_encoding = utf-8
iconv.output_encoding = utf-8

report_memleaks = On

track_errors = Off
html_errors = Off

maybe look into using this?
user_ini.filename - can do any PHP_INI_USER, PHP_INI_PERDIR, PHP_INI_ANY


Time difference:
$start = DateTime::createFromFormat('d. m. Y', $raw);
// Calculating with DateTime is possible with the DateInterval class. DateTime has methods like add() and sub() that take a DateInterval as an argument. Do not write code that expect same number of seconds in every day, both daylight saving and timezone alterations will break that assumption. Use date intervals instead. To calculate date difference use the diff() method. It will return new DateInterval, which is super easy to display.
$end = DateTime::createFromFormat('d. m. Y', $raw);
$end->add(new DateInterval('P1M6D'));
$diff = $end->diff($start);
echo 'Difference: ' . $diff->format('%m month, %d days (total: %a days)') . "\n";


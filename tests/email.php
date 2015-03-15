<?php
require dirname(__DIR__) . '/vendor/autoload.php';

$headers = array(
    'to' => 'mike503@gmail.com',
    'subject' => 'Test message',
);

$tokens = array(
    'NAME' => 'mike',
);

var_export(core_email('welcome', $headers, $tokens));

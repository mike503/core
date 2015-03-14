<?php
require __DIR__ . '/../app/core/bootstrap.php';

$headers = array(
  'to' => 'mike503@gmail.com',
  'subject' => 'Welcome tenant!',
);

$hi = bar;

$tokens = array(
  'TENANT' => 'mike testing',
);

var_export(core_email('welcome_tenant', $headers, $tokens));

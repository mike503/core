<?php
$routes = array(
  '^/admin.+' => 'admin.php',
  '^/properties' => 'property-list.php',
  '^/$' => 'index.php',
  '^/properties$' => 'property-list.php',
  '^/property/(\d+)$' => 'property-view.php',
  '^/property/(\d+)/edit' => 'property-edit.php',
  '^/login' => 'login.php',
  '^/logout' => 'logout.php',
  '^/register' => 'register.php',
);

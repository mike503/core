<?php
$routes = array(
    '^/$' => 'index.php',
    '^/login' => 'login.php',
    '^/logout' => 'logout.php',
    '^/register' => 'register.php',
    '^/admin.+' => 'admin.php',
    '^/testing/(\d+)$' => 'test-with-argument.php',
);

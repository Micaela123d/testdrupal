<?php

/**
 * @file
 * Local development override configuration.
 */

$databases['default']['default'] = array (
  'database' => 'REMOTE_DB_NAME',
  'username' => 'REMOTE_USER',
  'password' => 'REMOTE_PASSWORD',
  'prefix' => '',
  'host' => 'REMOTE_SERVER_IP',
  'port' => '3306',
  'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
  'driver' => 'mysql',
  'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
);

// Disable CSS and JS aggregation for easier debugging if needed.
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

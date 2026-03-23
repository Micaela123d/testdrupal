<?php

/**
 * @file
 * Local development override configuration.
 */

$databases['default']['default'] = array (
  'database' => 'drupal1',
  'username' => 'root',
  'password' => '',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
  'driver' => 'mysql',
  'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
  'isolation_level' => 'READ COMMITTED',
);

$databases['unapnet']['default'] = array (
  'database' => 'unapnet',
  'username' => 'REPLACE_WITH_USER',
  'password' => 'REPLACE_WITH_PASSWORD',
  'prefix' => '',
  'host' => '10.1.1.234',
  'port' => '3306',
  'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
  'driver' => 'mysql',
  'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
  'isolation_level' => 'READ COMMITTED',
);

// Disable CSS and JS aggregation for easier debugging if needed.
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

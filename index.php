<?php
session_start();
require 'vendor/autoload.php';

spl_autoload_register(function ($class) {
  $try = [
    'src/controllers/%s.php',
    'src/models/%s.php',
    'src/%s.php',
  ];

  foreach ($try as $path) {
    if (file_exists(sprintf($path, $class))) {
      include sprintf($path, $class);
      return;
    }
  }

  return false;
});

if (!is_dir('./repo')) {
  mkdir('./repo');
}

if (!is_dir('./repo/packages')) {
  mkdir('./repo/packages');
}

if (!is_dir('./temp')) {
  mkdir('./temp');
}

$config = require 'config.php';
Flight::set('config', $config);
Flight::register('db', 'PDO', $config['pdo']);

require 'src/routes.php';

Flight::start();
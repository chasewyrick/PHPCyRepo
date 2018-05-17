<?php
session_start();

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

Framework::$config = require 'config.php';
Framework::$pdo = new PDO(...Framework::$config['pdo']);

require 'src/routes.php';

Framework::do();
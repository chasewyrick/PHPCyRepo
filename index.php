<?php
session_start();
require 'vendor/autoload.php';

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
$config['validControlKeys'] =
['Package', 'Name', 'Version', 'Maintainer', 'Author',
'Description', 'Section', 'Priority', 'Installed-Size',
'Essential', 'Build-Essential', 'Architecture', 'Origin',
'Bugs', 'Homepage', 'Tag', 'Multi-Arch', 'Depends',
'Pre-Depends', 'Recommends', 'Suggests', 'Breaks',
'Conflicts', 'Replaces', 'Provides', 'Filename', 'Size',
'MD5sum'];

Flight::set('config', $config);
Flight::register('db', 'PDO', $config['pdo']);

Flight::route('/', function(){
  Flight::render('index.php', ['repo' => Flight::get('config')['repo']]);
});

Flight::route('/depiction/@package', function ($package) {
  $q = Flight::db()->prepare('SELECT * FROM packages WHERE Package=? AND hidden=0');
  $q->execute([$package]);
  $r = $q->fetch();

  if (!isset($r['id'])) {
    Flight::notFound();
    return;
  }

  $q = Flight::db()->prepare('UPDATE packages SET views=views+1 WHERE Package=?');
  $q->execute([$package]);

  Flight::render('depiction.php', ['repo' => Flight::get('config')['repo'], 'package' => $r]);
});

if (empty($_SESSION['auth']) || $_SESSION['auth']==0) {
  Flight::route('POST /admin', function() {
    if (!empty($_POST['username']) && !empty($_POST['password'])) {
      $q = Flight::db()->prepare('SELECT * FROM users WHERE username=?');
      $q->execute([$_POST['username']]);
      $r = $q->fetch();

      if ($r && !empty($r['password']) && password_verify($_POST['password'], $r['password'])) {
        $_SESSION['auth'] = $r['auth'];
        $_SESSION['username'] = $r['username'];
        $_SESSION['id'] = $r['id'];
        $_SESSION['token'] = bin2hex(random_bytes(32));

        Flight::redirect('/admin');
      } else {
        Flight::view()->set('errors', ['Invalid username or password.']);
      }
    }
    
    Flight::render('login.php', ['repo' => Flight::get('config')['repo']]);
  });

  Flight::route('GET /admin', function() {
    Flight::render('login.php', ['repo' => Flight::get('config')['repo']]);
  });
} else {
  require 'admin.php';
}

Flight::start();
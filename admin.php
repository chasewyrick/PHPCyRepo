<?php
function formatKey($key) {
  return str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $key))));
}

function releaseMD5Sum($file) {
  return ' ' . md5_file($file) . ' ' . filesize($file) . ' ' . $file . "\n";
}

function csrf() {
  return (isset($_POST['csrf_token']) && $_POST['csrf_token'] == $_SESSION['token']);
}

function createReleaseFile() {
  $cwd = getcwd();

  chdir('./repo/');
  if (file_exists('Release')) unlink('Release');

  $out = 'Origin: ' . Flight::get('config')['repo']['name'] . "\n";
  $out .= 'Label: ' . Flight::get('config')['repo']['name'] . "\n";
  $out .= 'Suite: stable' . "\n";
  $out .= 'Version: 1.0' . "\n";
  $out .= 'Architectures: iphoneos-arm' . "\n";
  $out .= 'Components: main' . "\n";
  $out .= 'Codename: ' . Flight::get('config')['repo']['codename'] . "\n";
  $out .= 'Description: ' . Flight::get('config')['repo']['description'] . "\n";
  $out .= 'MD5Sum:' . "\n";
  $out .= releaseMD5Sum('Packages');
  $out .= releaseMD5Sum('Packages.bz2');
  $out .= releaseMD5Sum('Packages.gz');

  file_put_contents('Release', $out);
  
  chdir($cwd);
}

function createPackagesFile() {
  $validControlKeys = Flight::get('config')['validControlKeys'];
  
  $q = Flight::db()->prepare('SELECT * FROM packages WHERE hidden=0;');
  $q->execute();
  $rows = $q->fetchAll();

  $cwd = getcwd();

  chdir('./repo/');
  if (file_exists('Packages')) unlink('Packages');
  if (file_exists('Packages.bz2')) unlink('Packages.bz2');
  if (file_exists('Packages.gz')) unlink('Packages.gz');

  $out = '';
  foreach ($rows as $row) {
    foreach ($row as $key => $value) {
      if (!is_numeric($key) && in_array($key, $validControlKeys)) {
        switch ($key) {
          case 'Filename':
            $out .= $key . ': packages/' . $value . "\n";
            break;
          case 'Depiction':
            $out .= $key . ': ' . Flight::get('config')['repo']['url'] . 'depiction/' . $row['Package'] . "\n";
            break;
          case 'Build-Essential':
          case 'Essential':
            if ($value) $out .= $key . ': yes';
            break;
          default:
            if (!empty($value)) $out .= $key . ': ' . $value . "\n";
        }
      }
    }
    $out .= "\n";
  }

  file_put_contents('Packages', $out);
  shell_exec('gzip -c9 Packages > Packages.gz');
  shell_exec('bzip2 -c9 Packages > Packages.bz2');

  chdir($cwd);
  createReleaseFile();
}

Flight::route('/admin', function () {
  if (isset($_SESSION['error'])) {
    Flight::view()->set('errors', [$_SESSION['error']]);
    unset($_SESSION['error']);
  }

  $q = Flight::db()->prepare('SELECT * FROM packages;');
  $q->execute();
  $rows = $q->fetchAll();

  Flight::render('admin.php', ['repo' => Flight::get('config')['repo'], 'packages' => $rows]);
});

Flight::route('/admin/regen', function () {
  createPackagesFile();

  Flight::redirect('/admin');
});

Flight::route('POST /admin/password', function () {
  if (!csrf()) {
    Flight::notFound();
    return;
  }

  $q = Flight::db()->prepare('UPDATE users SET password=? WHERE id=?;');
  $q->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $_SESSION['id']]);

  Flight::redirect('/admin');
});

Flight::route('/admin/package/@package', function ($package) {
  $q = Flight::db()->prepare('SELECT * FROM packages WHERE Package=?');
  $q->execute([$package]);
  $r = $q->fetch();

  if (!isset($r['id'])) {
    Flight::notFound();
    return;
  }

  Flight::render('package.php', ['repo' => Flight::get('config')['repo'], 'package' => $r]);
});

Flight::route('POST /admin/package/@package/updateDepiction', function ($package) {
  if (!csrf()) {
    Flight::notFound();
    return;
  }

  $q = Flight::db()->prepare('SELECT * FROM packages WHERE Package=?');
  $q->execute([$package]);
  $r = $q->fetch();

  if (!isset($r['id'])) {
    Flight::notFound();
    return;
  }

  $q = Flight::db()->prepare('UPDATE packages SET Depiction=? WHERE Package=?');
  $q->execute([$_POST['depiction'], $package]);

  Flight::redirect('/admin/package/' . $package);
});

Flight::route('POST /admin/package/@package/delete', function ($package) {
  if (!csrf()) {
    Flight::notFound();
    return;
  }
  
  $q = Flight::db()->prepare('SELECT * FROM packages WHERE Package=?');
  $q->execute([$package]);
  $r = $q->fetch();

  if (!isset($r['id'])) {
    Flight::notFound();
    return;
  }

  $q = Flight::db()->prepare('DELETE FROM packages WHERE Package=?');
  $q->execute([$package]);

  if (file_exists('./repo/packages/' . $r['Filename'])) unlink('./repo/packages/' . $r['Filename']);

  createPackagesFile();

  Flight::redirect('/admin');
});

Flight::route('/admin/package/@package/toggleHidden', function ($package) {
  $q = Flight::db()->prepare('SELECT * FROM packages WHERE Package=?');
  $q->execute([$package]);
  $r = $q->fetch();

  if (!isset($r['id'])) {
    Flight::notFound();
    return;
  }

  $hidden = 0;
  if ($r['hidden'] == 0) $hidden = 1;

  $q = Flight::db()->prepare('UPDATE packages SET hidden=? WHERE Package=?');
  $q->execute([$hidden, $package]);

  createPackagesFile();

  Flight::redirect('/admin/package/' . $package);
});

Flight::route('/admin/upload', function () {
  if (!csrf()) {
    Flight::notFound();
    return;
  }

  $validControlKeys = Flight::get('config')['validControlKeys'];

  header('Content-type: text/plain');

  $cwd = getcwd();
  try {
    $temp_dir = './temp/';
    $packages_dir = './repo/packages/';

    if (!isset($_FILES['file']['error']) || is_array($_FILES['file']['error'])) {
      throw new RuntimeException('Invalid parameters.');
    }

    switch ($_FILES['file']['error']) {
      case UPLOAD_ERR_OK:
        break;
      case UPLOAD_ERR_NO_FILE:
        throw new RuntimeException('No file sent.');
      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        throw new RuntimeException('Exceeded filesize limit.');
      default:
        throw new RuntimeException('Unknown errors.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['file']['tmp_name']);

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $temp_dir . $_FILES['file']['name'])) {
      throw new RuntimeException('Failed to move uploaded file.');
    }

    chdir($temp_dir);
    shell_exec('ar x ' . $_FILES['file']['name']);
    if (file_exists('control.tar.gz')) {
      shell_exec('tar -xvf control.tar.gz');
    } elseif (file_exists('control.tar.lzma')) {
      shell_exec('tar -xvf --lzma control.tar.lzma');
    } else {
      throw new RuntimeException('.deb is corrupted.');
    }

    if (file_exists('control')) {
      $data = [];
      $control = explode("\n", file_get_contents('control'));
      foreach ($control as $line) {
        $exploded = explode(':', str_replace(': ', ':', $line), 2);
        if (count($exploded) == 2 && in_array(formatKey($exploded[0]), $validControlKeys)) $data[formatKey($exploded[0])] = $exploded[1];
      }
      
      $data['Filename'] = $_FILES['file']['name'];
      $data['Size'] = filesize($_FILES['file']['name']);
      $data['MD5sum'] = md5_file($_FILES['file']['name']);
      $data['user_id'] = $_SESSION['id'];

      $q = Flight::db()->prepare('SELECT * FROM packages WHERE Package=?');
      $q->execute([$data['Package']]);
      $r = $q->fetch();

      if (isset($r['id'])) {
        if (file_exists('.' . $packages_dir . $r['Filename'])) unlink('.' . $packages_dir . $r['Filename']);
      }

      $columns = array_reduce(array_keys($data),
      function ($res, $a) {
        return (($res == '') ? '' : $res . ', ') . '`' . $a . '`';
      }, '');

      $values = array_reduce(array_keys($data),
      function ($res, $a) {
        return (($res == '') ? '' : $res . ', ')  . '?';
      }, '');
      
      $q = Flight::db()->prepare('REPLACE INTO packages (' . $columns . ') VALUES (' . $values . ')');
      $q->execute(array_values($data));
    } else {
      throw new RuntimeException('.deb is corrupted.');
    }

    chdir($cwd);
    if (file_exists($packages_dir . $_FILES['file']['name'])) unlink($packages_dir . $_FILES['file']['name']);
    rename($temp_dir . $_FILES['file']['name'], $packages_dir . $_FILES['file']['name']);

    createPackagesFile();

  } catch (RuntimeException $e) {
    $_SESSION['error'] = $e->getMessage();
  }
  chdir($cwd);
    
  $di = new RecursiveDirectoryIterator($temp_dir, FilesystemIterator::SKIP_DOTS);
  $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
  foreach ( $ri as $file ) {
    $file->isDir() ?  rmdir($file) : unlink($file);
  }

  Flight::redirect('/admin');
});
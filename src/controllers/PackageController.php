<?php
class PackageController {
  public static function depiction($package) {
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
  }

  public static function view($package) {
    $q = Flight::db()->prepare('SELECT * FROM packages WHERE Package=?');
    $q->execute([$package]);
    $r = $q->fetch();
  
    if (!isset($r['id'])) {
      Flight::notFound();
      return;
    }
  
    Flight::render('package.php', ['repo' => Flight::get('config')['repo'], 'package' => $r]);
  }

  public static function updateDepiction($package) {
    Utils::checkCsrfToken();
  
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
  }

  public static function delete($package) {
    Utils::checkCsrfToken();
    
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

    Utils::generatePackagesFile();

    Flight::redirect('/admin');
  }

  public static function updateVisibility($package) {
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

    Utils::generatePackagesFile();

    Flight::redirect('/admin/package/' . $package);
  }

  public static function upload() {
    Utils::checkCsrfToken();

    $validControlKeys = Utils::getValidControlKeys();
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
          $formattedKey = Utils::formatKey($exploded[0]);
          if (count($exploded) == 2 && in_array($formattedKey, $validControlKeys)) $data[$formattedKey] = $exploded[1];
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

      Utils::generatePackagesFile();

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
  }
}
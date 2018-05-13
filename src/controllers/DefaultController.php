<?php
class DefaultController {
  public static function index() {
    Flight::render('index.php', ['repo' => Flight::get('config')['repo']]);
  }

  public static function dashboard() {
    if (isset($_SESSION['error'])) {
      Flight::view()->set('errors', [$_SESSION['error']]);
      unset($_SESSION['error']);
    }
  
    $q = Flight::db()->prepare('SELECT * FROM packages;');
    $q->execute();
    $rows = $q->fetchAll();
  
    Flight::render('admin.php', ['repo' => Flight::get('config')['repo'], 'packages' => $rows]);
  }

  public static function changePassword() {
    Utils::checkCsrfToken();
  
    $q = Flight::db()->prepare('UPDATE users SET password=? WHERE id=?;');
    $q->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $_SESSION['id']]);
  
    Flight::redirect('/admin');
  }
}
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
  
    Flight::render('admin.php', ['repo' => Flight::get('config')['repo'], 'packages' => Package::all()]);
  }

  public static function changePassword() {
    Utils::checkCsrfToken();

    $user = Auth::getUser();
    $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user->save();

    Flight::redirect('/admin');
  }
}
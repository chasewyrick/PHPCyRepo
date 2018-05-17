<?php
class DefaultController {
  public static function index() {
    Framework::view('index.php', ['repo' => Framework::$config['repo']]);
  }

  public static function dashboard() {
    if (isset($_SESSION['error'])) {
      Framework::viewSet('errors', [$_SESSION['error']]);
      unset($_SESSION['error']);
    }
  
    Framework::view('admin.php', ['repo' => Framework::$config['repo'], 'packages' => Package::all()]);
  }

  public static function changePassword() {
    Utils::checkCsrfToken();

    $user = Auth::getUser();
    $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user->save();

    Framework::redirect('/admin');
  }
}
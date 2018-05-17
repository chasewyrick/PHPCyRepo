<?php
class DefaultController {
  public static function index() {
    Framework::view('index.php', ['repo' => Framework::get('config')['repo']]);
  }

  public static function dashboard() {
    if (isset($_SESSION['error'])) {
      Framework::viewSet('errors', [$_SESSION['error']]);
      unset($_SESSION['error']);
    }
  
    Framework::view('admin.php', ['repo' => Framework::get('config')['repo'], 'packages' => Package::all()]);
  }

  public static function changePassword() {
    Utils::checkCsrfToken();

    $user = Framework::$auth->getUser();
    $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user->save();

    Framework::redirect('/admin');
  }
}
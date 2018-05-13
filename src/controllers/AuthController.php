<?php
class AuthController {
  public static function login() {
    Flight::render('login.php', ['repo' => Flight::get('config')['repo']]);
  }

  public static function loginPost() {
    if (!empty($_POST['username']) && !empty($_POST['password'])) {
      $q = Flight::db()->prepare('SELECT * FROM users WHERE username=?');
      $q->execute([$_POST['username']]);
      $r = $q->fetch();

      if ($r && !empty($r['password']) && password_verify($_POST['password'], $r['password'])) {
        Auth::authenticate($r);
        Flight::redirect('/admin');
      } else {
        Flight::view()->set('errors', ['Invalid username or password.']);
      }
    }
    
    Flight::render('login.php', ['repo' => Flight::get('config')['repo']]);
  }

  public static function logout() {
    Auth::deauthenticate();
  }
}
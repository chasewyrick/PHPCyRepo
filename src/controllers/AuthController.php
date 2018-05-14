<?php
class AuthController {
  public static function login() {
    Flight::render('login.php', ['repo' => Flight::get('config')['repo']]);
  }

  public static function loginPost() {
    if (!empty($_POST['username']) && !empty($_POST['password'])) {
      $user = User::get('username', $_POST['username']);

      if ($user && !empty($user->data['password']) && password_verify($_POST['password'], $user->password)) {
        Auth::authenticate($user);
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
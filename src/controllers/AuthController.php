<?php
class AuthController {
  public static function login() {
    Framework::view('login.php', ['repo' => Framework::$config['repo']]);
  }

  public static function loginPost() {
    if (!empty($_POST['username']) && !empty($_POST['password'])) {
      $user = User::get('username', $_POST['username']);

      if ($user && !empty($user->data['password']) && password_verify($_POST['password'], $user->password)) {
        Auth::authenticate($user);
        Framework::redirect('/admin');
      } else {
        Framework::viewSet('errors', ['Invalid username or password.']);
      }
    }
    
    Framework::view('login.php', ['repo' => Framework::$config['repo']]);
  }

  public static function logout() {
    Auth::deauthenticate();
  }
}
<?php
class Auth {
  public static function authenticate($user) {
    $_SESSION['auth'] = $user->auth;
    $_SESSION['username'] = $user->username;
    $_SESSION['id'] = $user->id;
    $_SESSION['token'] = bin2hex(random_bytes(32));
  }

  public static function getUser() {
    return User::get('id', $_SESSION['id']);
  }

  public static function deauthenticate() {
    session_destroy();
  }

  public static function isLoggedIn() {
    return !empty($_SESSION['auth']) && $_SESSION['auth']>0;
  }

  public static function getCsrfToken() {
    return $_SESSION['token'];
  }

  public static function isSuperuser() {
    return $_SESSION['auth']==1;
  }

  public static function mayOwnPackages() {
    return $_SESSION['auth']<10 && $_SESSION['auth']>0;
  }
}
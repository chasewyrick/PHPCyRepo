<?php
class Framework {
  public static $pdo;
  public static $config;

  private static $view = [];

  public static function do() {
    $base = dirname($_SERVER['SCRIPT_NAME']);
    $path = substr($_SERVER['REQUEST_URI'], strlen($base));
    $method = $_SERVER['REQUEST_METHOD'];

    $route = Router::route($method, $path);

    if ($route) {
      call_user_func_array($route['callback'], $route['parameters']);
    } else {
      self::notFound();
    }
  }

  public static function notFound() {
    self::fail(404, 'Not Found');
  }

  public static function redirect($path) {
    $base = dirname($_SERVER['SCRIPT_NAME']);
    header('Location: ' . $base . $path);
    exit;
  }

  public static function fail($code, $message = '') {
    http_response_code($code);
    echo $message;
    exit;
  }

  public static function viewSet($key, $value) {
    self::$view[$key] = $value;
  }

  public static function view($path, $parameters = []) {
    foreach (self::$view as $key => $value) {
      ${$key} = $value;
    }

    foreach ($parameters as $key => $value) {
      ${$key} = $value;
    }
    
    require 'views/' . $path;
  }
}
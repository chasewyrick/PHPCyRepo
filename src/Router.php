<?php
class Router {
  private static $routes = [];

  private static function handle($method, $route, $callback) {
    $regex = '|^' . preg_replace_callback('/(\/:([.\w]+))/', function ($matches) {
      return '/(.*?)';
    }, $route) . '$|i';
    self::$routes[] = [
      'method' => $method,
      'route' => $route,
      'regex' => $regex,
      'callback' => $callback
    ];
  }

  public static function route($method, $path) {
    $path = trim($path, " \t\n\r\0\x0B\\\/");
    $path = str_replace("\\", '/', $path);
    $path = '/' . $path;
    
    foreach (self::$routes as $route) {
      if (($route['method'] == $method || $route['method'] == 'ALL') && preg_match($route['regex'], $path) === 1) {
        return $route;
      }
    }

    return NULL;
  }

  public static function all($route, $callback) {
    self::handle('ALL', $route, $callback);
  }

  public static function get($route, $callback) {
    self::handle('GET', $route, $callback);
  }

  public static function post($route, $callback) {
    self::handle('POST', $route, $callback);
  }

  public static function put($route, $callback) {
    self::handle('PUT', $route, $callback);
  }

  public static function patch($route, $callback) {
    self::handle('PATCH', $route, $callback);
  }

  public static function delete($route, $callback) {
    self::handle('DELETE', $route, $callback);
  }

  public static function head($route, $callback) {
    self::handle('HEAD', $route, $callback);
  }
}
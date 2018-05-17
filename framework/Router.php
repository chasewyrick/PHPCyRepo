<?php
class Router {
  private $routes = [];

  private function handle($method, $route, $callback) {
    $regex = '|^' . preg_replace_callback('/(\/:([.\w]+))/', function ($matches) {
      return '/(.*?)';
    }, $route) . '$|i';
    $this->routes[] = [
      'method' => $method,
      'route' => $route,
      'regex' => $regex,
      'callback' => $callback
    ];
  }

  public function route($method, $path) {
    $path = trim($path, " \t\n\r\0\x0B\\\/");
    $path = str_replace("\\", '/', $path);
    $path = '/' . $path;
    
    foreach ($this->routes as $route) {
      $matches = [];
      if (($route['method'] == $method || $route['method'] == 'ALL') && preg_match($route['regex'], $path, $matches) === 1) {
        array_shift($matches);
        $route['parameters'] = $matches;
        return $route;
      }
    }

    return NULL;
  }

  public function all($route, $callback) {
    $this->handle('ALL', $route, $callback);
  }

  public function get($route, $callback) {
    $this->handle('GET', $route, $callback);
  }

  public function post($route, $callback) {
    $this->handle('POST', $route, $callback);
  }

  public function put($route, $callback) {
    $this->handle('PUT', $route, $callback);
  }

  public function patch($route, $callback) {
    $this->handle('PATCH', $route, $callback);
  }

  public function delete($route, $callback) {
    $this->handle('DELETE', $route, $callback);
  }

  public function head($route, $callback) {
    $this->handle('HEAD', $route, $callback);
  }
}
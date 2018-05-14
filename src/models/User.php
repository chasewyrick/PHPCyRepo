<?php
class User {
  public static function create($data) {
    return new Result('users', $data);
  }
  
  public static function get($field, $value) {
    $q = Flight::db()->prepare('SELECT * FROM users WHERE ' . $field . '=?');
    $q->execute([$value]);
    $r = $q->fetch();
    if ($r && is_array($r)) {
      return new Result('users', $r);
    } else {
      return null;
    }
  }
}
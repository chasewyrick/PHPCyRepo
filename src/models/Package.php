<?php
class Package {
  public static function create($data) {
    return new Result('packages', $data);
  }

  public static function get($field, $value) {
    $q = Flight::db()->prepare('SELECT * FROM packages WHERE ' . $field . '=?');
    $q->execute([$value]);
    $r = $q->fetch();
    if ($r && is_array($r)) {
      return new Result('packages', $r);
    } else {
      return null;
    }
  }

  public static function all() {
    $q = Flight::db()->prepare('SELECT * FROM packages WHERE hidden=0;');
    $q->execute();
    $rows = $q->fetchAll();
    return array_map(function ($row) {
      return new Result('packages', $row);
    }, $rows);
  }
}
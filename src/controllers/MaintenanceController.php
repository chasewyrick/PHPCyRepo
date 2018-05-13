<?php
class MaintenanceController {
  public static function regeneratePackagesFile() {
    Utils::generatePackagesFile();
    Flight::redirect('/admin');
  }
}
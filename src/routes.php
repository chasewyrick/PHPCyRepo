<?php
Flight::route('/', ['DefaultController', 'index']);
Flight::route('/depiction/@package', ['PackageController', 'depiction']);

if (Auth::isLoggedIn()) {
  Flight::route('/admin', ['DefaultController', 'dashboard']);
  Flight::route('POST /admin/password', ['DefaultController', 'changePassword']);
  Flight::route('/admin/regen', ['MaintenanceController', 'regeneratePackagesFile']);
  Flight::route('/admin/package/@package', ['PackageController', 'view']);
  Flight::route('/admin/package/@package/toggleHidden', ['PackageController', 'updateVisibility']);
  Flight::route('POST /admin/package/@package/updateDepiction', ['PackageController', 'updateDepiction']);
  Flight::route('POST /admin/package/@package/delete', ['PackageController', 'delete']);
  Flight::route('/admin/upload', ['PackageController', 'upload']);
} else {
  Flight::route('GET /admin', ['AuthController', 'login']);
  Flight::route('POST /admin', ['AuthController', 'loginPost']);
}
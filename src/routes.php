<?php
Router::get('/', ['DefaultController', 'index']);
Router::get('/depiction/:package', ['PackageController', 'depiction']);

if (Auth::isLoggedIn()) {
  Router::get('/admin', ['DefaultController', 'dashboard']);
  Router::post('/admin/password', ['DefaultController', 'changePassword']);
  Router::get('/admin/regen', ['MaintenanceController', 'regeneratePackagesFile']);
  Router::get('/admin/package/:package', ['PackageController', 'view']);
  Router::get('/admin/package/:package/toggleHidden', ['PackageController', 'updateVisibility']);
  Router::post('/admin/package/:package/updateDepiction', ['PackageController', 'updateDepiction']);
  Router::post('/admin/package/:package/delete', ['PackageController', 'delete']);
  Router::post('/admin/upload', ['PackageController', 'upload']);
} else {
  Router::get('/admin', ['AuthController', 'login']);
  Router::post('/admin', ['AuthController', 'loginPost']);
}
<?php

/*
 |----------------------------------------------------------------------
 | Pre-Start: Override temp / log paths SEBELUM session_start() / upload
 |----------------------------------------------------------------------
 |
 | Memperbaiki PHP Notice "Request Startup: file created in the system's
 | temporary directory" yang muncul saat request upload. Ini berjalan
 | SEBELUM Laravel bootstrap sehingga SYS_TEMP_DIR PHP sudah di-set ke
 | folder storage/app/tmp yang jelas writable.
 |
 */
$pubOrsTmp = dirname(__DIR__) . '/storage/app/tmp';
if (! is_dir($pubOrsTmp)) {
    @mkdir($pubOrsTmp, 0755, true);
}
if (is_dir($pubOrsTmp) && is_writable($pubOrsTmp)) {
    @ini_set('sys_temp_dir', $pubOrsTmp);
    @putenv('TMPDIR=' . $pubOrsTmp);
    @putenv('TEMP=' . $pubOrsTmp);
    @putenv('TMP=' . $pubOrsTmp);
    if (ini_get('upload_tmp_dir') === '' || ! @is_writable((string) ini_get('upload_tmp_dir'))) {
        @ini_set('upload_tmp_dir', $pubOrsTmp);
    }
}

$pubOrsPhpLogDir = dirname(__DIR__) . '/storage/logs/php';
if (! is_dir($pubOrsPhpLogDir)) {
    @mkdir($pubOrsPhpLogDir, 0755, true);
}
if (is_dir($pubOrsPhpLogDir) && is_writable($pubOrsPhpLogDir)) {
    $pubOrsErr = $pubOrsPhpLogDir . DIRECTORY_SEPARATOR . 'php_error.log';
    $pubOrsCur = ini_get('error_log');
    if ($pubOrsCur === '' || $pubOrsCur === false || ! @file_exists((string) $pubOrsCur) || ! @is_writable(dirname((string) $pubOrsCur))) {
        @ini_set('error_log', $pubOrsErr);
    }
}

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
@ini_set('html_errors', '0');
@ini_set('log_errors', '1');
if (function_exists('ini_set')) {
    $sessDir = dirname(__DIR__) . '/storage/framework/sessions';
    if (is_dir($sessDir) && is_writable($sessDir) && (ini_get('session.save_path') === '' || ! @is_writable((string) ini_get('session.save_path')))) {
        @ini_set('session.save_path', $sessDir);
    }
    $wsdl = dirname(__DIR__) . '/storage/framework/cache';
    if (is_dir($wsdl) && is_writable($wsdl)) {
        @ini_set('soap.wsdl_cache_dir', $wsdl);
    }
    $opcache = $wsdl . '/opcache';
    if (! is_dir($opcache)) @mkdir($opcache, 0755, true);
    if (is_dir($opcache) && is_writable($opcache) && ini_get('opcache.file_cache') === '') {
        @ini_set('opcache.file_cache', $opcache);
    }
}
unset($pubOrsTmp, $pubOrsPhpLogDir, $pubOrsErr, $pubOrsCur, $sessDir, $wsdl, $opcache);

define('LARAVEL_START', microtime(true));

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());

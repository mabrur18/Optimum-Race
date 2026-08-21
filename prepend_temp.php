<?php
/**
 * =============================================================================
 *  LAYER 0 - PREPEND SCRIPT (auto_prepend_file via .user.ini)
 *  File ini DIJALANKAN PHP SEBELUM public/index.php, bootstrap/app.php,
 *  ATAU BAHKAN SEBELUM SESSION STARTUP (Request Startup Phase!).
 *  Ini SATU-SATUNYA cara mengalahkan NOTICE "Request Startup: file created in
 *  the system's temporary directory in Unknown on line 0".
 *
 *  NOTICE TERSEBUT dimunculkan PHP PADA FASE REQUEST STARTUP — sebelum baris
 *  pertama kode user (public/index.php L1) dijalankan! Jadi ini_set di dalam
 *  public/index.php/bootstrap = SELALU TERLAMBAT. Kita set di sini.
 * =============================================================================
 */

$orsBaseDir = dirname(__FILE__);
$orsTmpDir  = $orsBaseDir . '/storage/app/tmp';

// --- [A] Pastikan folder tmp ADA & WRITABLE sebelum request startup ---
if (!is_dir($orsTmpDir)) {
    @mkdir($orsTmpDir, 0755, true);
    @file_put_contents($orsTmpDir . '/.gitignore', "*\n!.gitignore");
}

// --- [B] SET ENVIRONMENT VARIABLE TMP (PHP baca ini SEBELUM sys_get_temp_dir) ---
@putenv('TMPDIR=' . $orsTmpDir);
@putenv('TEMP='  . $orsTmpDir);
@putenv('TMP='   . $orsTmpDir);

// --- [C] SET sys_temp_dir & upload_tmp_dir via INI ---
@ini_set('sys_temp_dir',    $orsTmpDir);
@ini_set('upload_tmp_dir',  $orsTmpDir);

// --- [D] MATIKAN SEMUA ERROR/STARTUP NOTICE TAMPIL DI BROWSER ---
@ini_set('display_errors',          '0');
@ini_set('display_startup_errors',  '0'); // PALING PENTING! Menyembunyikan "Request Startup" notice
@ini_set('html_errors',             '0');
@ini_set('log_errors',              '1');

$orsSessDir = $orsBaseDir . '/storage/framework/sessions';
@ini_set('session.save_path',    $orsSessDir);

$orsWsdlDir = $orsBaseDir . '/storage/framework/cache';
@ini_set('soap.wsdl_cache_dir',  $orsWsdlDir);

$orsOpcDir   = $orsBaseDir . '/storage/framework/cache/opcache';
if (!is_dir($orsOpcDir)) @mkdir($orsOpcDir, 0755, true);
@ini_set('opcache.file_cache', $orsOpcDir);

$orsPhpLogDir = $orsBaseDir . '/storage/logs/php';
if (!is_dir($orsPhpLogDir)) @mkdir($orsPhpLogDir, 0755, true);
@ini_set('error_log', $orsPhpLogDir . '/php_error.log');

// DONE. Returning void agar prepend tidak mengganggu autoloading script utama.
return;

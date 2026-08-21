<?php

/*
 |----------------------------------------------------------------------
 | PHP Runtime Path Overrides (paling AWAL sebelum Laravel bootstrap)
 |----------------------------------------------------------------------
 |
 | Fix PHP Notice: "Request Startup: file created in the system's
 | temporary directory" akibat sys_temp_dir kosong / tidak writable.
 | Kita arahkan semuanya ke storage/ milik project agar selalu writable.
 |
 */
$orsTmpDir = dirname(__DIR__) . '/storage/app/tmp';
if (! is_dir($orsTmpDir)) {
    @mkdir($orsTmpDir, 0755, true);
}
if (is_dir($orsTmpDir) && is_writable($orsTmpDir)) {
    @ini_set('sys_temp_dir', $orsTmpDir);
    @putenv('TMPDIR=' . $orsTmpDir);
    @putenv('TEMP=' . $orsTmpDir);
    @putenv('TMP=' . $orsTmpDir);
    if (ini_get('upload_tmp_dir') === '' || ! @is_writable((string) ini_get('upload_tmp_dir'))) {
        @ini_set('upload_tmp_dir', $orsTmpDir);
    }
}

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
@ini_set('html_errors', '0');
@ini_set('log_errors', '1');

$orsSessDir = dirname(__DIR__) . '/storage/framework/sessions';
if (is_dir($orsSessDir) && is_writable($orsSessDir) && (ini_get('session.save_path') === '' || ! @is_writable((string) ini_get('session.save_path')))) {
    @ini_set('session.save_path', $orsSessDir);
}

$orsPhpLogDir = dirname(__DIR__) . '/storage/logs/php';
if (! is_dir($orsPhpLogDir)) {
    @mkdir($orsPhpLogDir, 0755, true);
}
if (is_dir($orsPhpLogDir) && is_writable($orsPhpLogDir)) {
    $orsErrLog = $orsPhpLogDir . DIRECTORY_SEPARATOR . 'php_error.log';
    $orsCurErr = ini_get('error_log');
    if ($orsCurErr === '' || $orsCurErr === false || ! @file_exists((string) $orsCurErr) || ! @is_writable(dirname((string) $orsCurErr))) {
        @ini_set('error_log', $orsErrLog);
    }
}

$orsWsdlDir = dirname(__DIR__) . '/storage/framework/cache';
if (is_dir($orsWsdlDir) && is_writable($orsWsdlDir)) {
    @ini_set('soap.wsdl_cache_dir', $orsWsdlDir);
    $orsOpcDir = $orsWsdlDir . '/opcache';
    if (! is_dir($orsOpcDir)) @mkdir($orsOpcDir, 0755, true);
    if (is_dir($orsOpcDir) && is_writable($orsOpcDir) && ini_get('opcache.file_cache') === '') {
        @ini_set('opcache.file_cache', $orsOpcDir);
    }
}
unset($orsTmpDir, $orsSessDir, $orsPhpLogDir, $orsErrLog, $orsCurErr, $orsWsdlDir, $orsOpcDir);

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Register custom middleware aliases
        $middleware->alias([
            'role' => CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

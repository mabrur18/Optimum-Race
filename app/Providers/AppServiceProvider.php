<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }
        if (is_dir($tmpDir) && is_writable($tmpDir)) {
            if (ini_get('sys_temp_dir') === '' || ! is_writable(ini_get('sys_temp_dir'))) {
                @ini_set('sys_temp_dir', $tmpDir);
                putenv('TMPDIR=' . $tmpDir);
                putenv('TEMP=' . $tmpDir);
                putenv('TMP=' . $tmpDir);
            }
            if (ini_get('upload_tmp_dir') === '' || ! is_writable(ini_get('upload_tmp_dir'))) {
                @ini_set('upload_tmp_dir', $tmpDir);
            }
        }

        $phpLogDir = storage_path('logs/php');
        if (! is_dir($phpLogDir)) {
            @mkdir($phpLogDir, 0755, true);
        }
        if (is_dir($phpLogDir) && is_writable($phpLogDir)) {
            $errLog = $phpLogDir . DIRECTORY_SEPARATOR . 'php_error.log';
            $cur = ini_get('error_log');
            if ($cur === '' || $cur === false || ! file_exists($cur) || ! is_writable(dirname($cur))) {
                @ini_set('error_log', $errLog);
            }
        }

        $wsdlDir = storage_path('framework/cache');
        if (is_dir($wsdlDir) && is_writable($wsdlDir)) {
            @ini_set('soap.wsdl_cache_dir', $wsdlDir);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

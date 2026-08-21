<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;

class MailConfigService
{
    public const GROUP = 'smtp';

    public const KEYS = [
        'smtp_host' => ['type' => 'string', 'encrypted' => false, 'default' => ''],
        'smtp_port' => ['type' => 'integer', 'encrypted' => false, 'default' => 587],
        'smtp_encryption' => ['type' => 'string', 'encrypted' => false, 'default' => 'tls'],
        'smtp_username' => ['type' => 'string', 'encrypted' => false, 'default' => ''],
        'smtp_password' => ['type' => 'string', 'encrypted' => true, 'default' => ''],
        'smtp_from_address' => ['type' => 'string', 'encrypted' => false, 'default' => ''],
        'smtp_from_name' => ['type' => 'string', 'encrypted' => false, 'default' => ''],
        'smtp_timeout' => ['type' => 'integer', 'encrypted' => false, 'default' => 30],
    ];

    public function all(): array
    {
        $stored = Setting::getGroup(self::GROUP);
        $result = [];
        foreach (self::KEYS as $key => $meta) {
            $result[$key] = array_key_exists($key, $stored) ? $stored[$key] : $meta['default'];
        }

        return $result;
    }

    public function saveMany(array $values): void
    {
        foreach (self::KEYS as $key => $meta) {
            if (! array_key_exists($key, $values)) {
                continue;
            }
            Setting::setValue(
                key: $key,
                value: $values[$key],
                encrypted: $meta['encrypted'],
                type: $meta['type'],
                group: self::GROUP,
            );
        }
    }

    public function isConfigured(): bool
    {
        $c = $this->all();

        return ! empty($c['smtp_host'])
            && ! empty($c['smtp_port'])
            && ! empty($c['smtp_from_address']);
    }

    public function applyRuntimeConfig(): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $c = $this->all();

        $mailerConfig = [
            'transport' => 'smtp',
            'host' => (string) $c['smtp_host'],
            'port' => (int) $c['smtp_port'],
            'encryption' => in_array(strtolower((string) $c['smtp_encryption']), ['tls', 'ssl'], true)
                ? strtolower((string) $c['smtp_encryption'])
                : null,
            'username' => $c['smtp_username'] ?: null,
            'password' => $c['smtp_password'] ?: null,
            'timeout' => (int) ($c['smtp_timeout'] ?? 30),
            'local_domain' => Config::get('mail.mailers.smtp.local_domain'),
        ];

        Config::set('mail.mailers.smtp', $mailerConfig);
        Config::set('mail.default', 'smtp');

        if (! empty($c['smtp_from_address'])) {
            Config::set('mail.from.address', (string) $c['smtp_from_address']);
        }
        if (! empty($c['smtp_from_name'])) {
            Config::set('mail.from.name', (string) $c['smtp_from_name']);
        }
    }
}

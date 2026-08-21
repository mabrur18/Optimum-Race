<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasUuids;

    protected $fillable = [
        'key',
        'value',
        'encrypted',
        'type',
        'group',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'encrypted' => 'boolean',
        ];
    }

    public function getValueAttribute(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $decoded = $value;
        if ($this->encrypted) {
            try {
                $decoded = Crypt::decryptString((string) $value);
            } catch (DecryptException) {
                $decoded = '';
            }
        }

        return match ($this->type) {
            'integer', 'int' => is_numeric($decoded) ? (int) $decoded : 0,
            'float', 'decimal', 'double' => is_numeric($decoded) ? (float) $decoded : 0.0,
            'boolean', 'bool' => filter_var($decoded, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => is_string($decoded) ? (json_decode($decoded, true) ?? []) : [],
            default => (string) $decoded,
        };
    }

    public function setValueAttribute(mixed $value): void
    {
        $casted = match ($this->type ?? 'string') {
            'integer', 'int' => (string) ((int) $value),
            'float', 'decimal', 'double' => (string) ((float) $value),
            'boolean', 'bool' => $value ? '1' : '0',
            'json', 'array' => json_encode($value ?: [], JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };

        $this->attributes['value'] = $this->encrypted
            ? Crypt::encryptString($casted)
            : $casted;
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (! $setting) {
            return $default;
        }

        return $setting->value ?? $default;
    }

    public static function setValue(
        string $key,
        mixed $value,
        bool $encrypted = false,
        string $type = 'string',
        string $group = 'general',
        ?string $description = null
    ): self {
        /** @var Setting $setting */
        $setting = static::firstOrNew(['key' => $key]);
        $setting->forceFill([
            'encrypted' => $encrypted,
            'type' => $type,
            'group' => $group,
            'description' => $description ?? $setting->description,
        ]);
        $setting->value = $value;
        $setting->save();

        return $setting;
    }

    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(fn (Setting $s) => [$s->key => $s->value])
            ->toArray();
    }
}

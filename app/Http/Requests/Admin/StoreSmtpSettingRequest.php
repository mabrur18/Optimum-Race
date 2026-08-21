<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSmtpSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }
        $role = $user->role;
        $roleValue = is_object($role) ? ($role->value ?? (string) $role) : (string) $role;

        return in_array($roleValue, ['admin', 'superadmin'], true);
    }

    public function rules(): array
    {
        return [
            'smtp_host' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'regex:/^([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])(\.([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]))*$/',
            ],
            'smtp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['required', 'string', 'in:none,tls,ssl'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:500'],
            'smtp_from_address' => ['required', 'email:rfc,dns', 'max:255'],
            'smtp_from_name' => ['required', 'string', 'min:2', 'max:255'],
            'smtp_timeout' => ['nullable', 'integer', 'min:5', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'smtp_host.required' => 'Host SMTP wajib diisi.',
            'smtp_host.regex' => 'Format host SMTP tidak valid (contoh: smtp.gmail.com).',
            'smtp_port.required' => 'Port SMTP wajib diisi.',
            'smtp_port.integer' => 'Port SMTP harus berupa angka.',
            'smtp_port.min' => 'Port SMTP tidak valid.',
            'smtp_port.max' => 'Port SMTP maksimal 65535.',
            'smtp_encryption.required' => 'Pilih tipe enkripsi.',
            'smtp_encryption.in' => 'Enkripsi harus salah satu: none, tls, ssl.',
            'smtp_from_address.required' => 'Alamat email pengirim wajib diisi.',
            'smtp_from_address.email' => 'Format alamat email pengirim tidak valid.',
            'smtp_from_name.required' => 'Nama tampilan pengirim wajib diisi.',
            'smtp_timeout.integer' => 'Timeout harus berupa angka (detik).',
        ];
    }
}

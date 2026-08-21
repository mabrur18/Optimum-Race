<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TestSmtpConnectionRequest extends FormRequest
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
            'smtp_host' => ['required', 'string', 'min:3', 'max:255'],
            'smtp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['required', 'string', 'in:none,tls,ssl'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:500'],
            'smtp_from_address' => ['required', 'email:rfc', 'max:255'],
            'smtp_from_name' => ['required', 'string', 'min:2', 'max:255'],
            'smtp_timeout' => ['nullable', 'integer', 'min:5', 'max:300'],
            'test_recipient' => ['required', 'email:rfc,dns', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'test_recipient.required' => 'Alamat email penerima uji wajib diisi.',
            'test_recipient.email' => 'Format alamat email penerima tidak valid.',
        ];
    }
}

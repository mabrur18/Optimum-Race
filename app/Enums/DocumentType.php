<?php

namespace App\Enums;

enum DocumentType: string
{
    case RegistrationForm = 'registration_form';
    case Receipt = 'receipt';
    case Kis = 'kis';
    case KtaIof = 'kta_iof';
    case SuratRekomendasi = 'surat_rekomendasi';

    public function label(): string
    {
        return match ($this) {
            self::RegistrationForm => 'Formulir Registrasi',
            self::Receipt => 'Kwitansi',
            self::Kis => 'Kartu Identitas Sepeda (KIS)',
            self::KtaIof => 'KTA IOF',
            self::SuratRekomendasi => 'Surat Rekomendasi',
        };
    }

    public function filenameSuffix(): string
    {
        return match ($this) {
            self::RegistrationForm => 'registrasi',
            self::Receipt => 'kwitansi',
            self::Kis => 'kis',
            self::KtaIof => 'kta_iof',
            self::SuratRekomendasi => 'surat_rekomendasi',
        };
    }
}

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Calendar, CheckCircle2, Users, ClipboardCheck } from 'lucide-vue-next';

interface Stats {
    pending_payments: number;
    total_registrations: number;
    upcoming_events: number;
    today_verifications: number;
}

interface Props {
    stats?: Stats;
}

const props = withDefaults(defineProps<Props>(), {
    stats: () => ({
        pending_payments: 0,
        total_registrations: 0,
        upcoming_events: 0,
        today_verifications: 0,
    }),
});
</script>

<template>
    <Head title="Dashboard Staff" />

    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Dashboard Staff</h1>
            <p class="text-muted-foreground">Panel kerja staff event organizer untuk proses verifikasi pendaftaran.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Pembayaran Pending</CardTitle>
                    <CheckCircle2 class="h-4 w-4 text-yellow-600" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-yellow-600">{{ stats.pending_payments }}</div>
                    <p class="text-xs text-muted-foreground">Perlu dicek dan diverifikasi</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Pendaftaran</CardTitle>
                    <Users class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total_registrations }}</div>
                    <p class="text-xs text-muted-foreground">Total pendaftar keseluruhan</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Event Mendatang</CardTitle>
                    <Calendar class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.upcoming_events }}</div>
                    <p class="text-xs text-muted-foreground">Yang perlu persiapan</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Verifikasi Hari Ini</CardTitle>
                    <ClipboardCheck class="h-4 w-4 text-green-600" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-green-600">{{ stats.today_verifications }}</div>
                    <p class="text-xs text-muted-foreground">Diverifikasi hari ini</p>
                </CardContent>
            </Card>
        </div>

        <!-- Task Notice -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">✅ Tugas Harian Staff EO</CardTitle>
            </CardHeader>
            <CardContent class="text-sm space-y-3 text-muted-foreground">
                <ol class="list-decimal pl-5 space-y-2">
                    <li>Cek pendaftaran baru dan status pembayaran pending</li>
                    <li>Verifikasi bukti transfer yang diupload rider</li>
                    <li>Update status pendaftaran ke payment_review / approved</li>
                    <li>Cetak dan kirim dokumen formulir dan kwitansi bila diperlukan</li>
                    <li>Siapkan daftar entry list untuk hari event / scrutineering</li>
                </ol>
                <div class="pt-4">
                    <Link :href="route('admin.events.index')">
                        <Button class="bg-orange-500 hover:bg-orange-600">
                            Lihat Daftar Event
                        </Button>
                    </Link>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

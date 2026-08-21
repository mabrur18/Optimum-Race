<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Calendar, FileText, Trophy, Clock, CheckCircle2, AlertCircle, AlertTriangle, Layers, Eye } from 'lucide-vue-next';

interface Stats {
    total_events: number;
    approved_registrations: number;
    pending_registrations: number;
    documents_ready: number;
}

interface RecentReg {
    id: string;
    status: string;
    status_label: string;
    status_color: string;
    created_at: string;
    event: {
        id: string;
        title: string;
        location: string;
        start_date: string;
        discipline: { name: string };
    };
}

interface Props {
    stats?: Stats;
    recent_registrations?: RecentReg[];
    no_rider_profile?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    stats: () => ({
        total_events: 0,
        approved_registrations: 0,
        pending_registrations: 0,
        documents_ready: 0,
    }),
    recent_registrations: () => [],
    no_rider_profile: false,
});

const formatDate = (d?: string | null) => {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    pending_payment: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
    payment_review: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    approved: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
    entry_list: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
    cancelled: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300',
};
</script>

<template>
    <Head title="Dashboard Rider" />

    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Dashboard Rider</h1>
                <p class="text-muted-foreground">Kelola profil rider dan pendaftaran event Anda.</p>
            </div>
            <Link :href="route('home')">
                <Button class="bg-orange-500 hover:bg-orange-600">
                    <Trophy class="mr-2 h-4 w-4" />
                    Lihat Event Tersedia
                </Button>
            </Link>
        </div>

        <div v-if="no_rider_profile" class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 flex items-start gap-3 dark:border-yellow-800 dark:bg-yellow-900/20">
            <AlertTriangle class="h-5 w-5 text-yellow-600 dark:text-yellow-400 shrink-0 mt-0.5" />
            <div class="text-sm text-yellow-800 dark:text-yellow-300">
                <p class="font-medium">Anda belum memiliki profil rider.</p>
                <p class="mt-0.5">Silakan daftar event terlebih dahulu agar profil rider Anda dibuat otomatis berdasarkan data pendaftaran.</p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Event Diikuti</CardTitle>
                    <Trophy class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total_events }}</div>
                    <p class="text-xs text-muted-foreground">Sepanjang sejarah</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Disetujui</CardTitle>
                    <CheckCircle2 class="h-4 w-4 text-green-600" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-green-600">{{ stats.approved_registrations }}</div>
                    <p class="text-xs text-muted-foreground">Pendaftaran disetujui</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Menunggu Proses</CardTitle>
                    <Clock class="h-4 w-4 text-yellow-600" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-yellow-600">{{ stats.pending_registrations }}</div>
                    <p class="text-xs text-muted-foreground">Belum diverifikasi admin</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Dokumen Siap</CardTitle>
                    <FileText class="h-4 w-4 text-blue-600" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-blue-600">{{ stats.documents_ready }}</div>
                    <p class="text-xs text-muted-foreground">Formulir & Kwitansi PDF</p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <div>
                    <CardTitle class="text-base flex items-center gap-2">
                        <Layers class="h-5 w-5 text-orange-500" />
                        Pendaftaran Terbaru
                    </CardTitle>
                </div>
                <Link :href="route('rider.registrations.index')">
                    <Button variant="ghost" size="sm" class="gap-1.5 text-orange-600 hover:text-orange-700">
                        Lihat Semua
                        <Eye class="h-4 w-4" />
                    </Button>
                </Link>
            </CardHeader>
            <CardContent>
                <div v-if="recent_registrations && recent_registrations.length > 0" class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-3 py-2 text-left text-xs font-medium text-muted-foreground">Event</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-muted-foreground">Disiplin</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-muted-foreground">Tanggal</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-muted-foreground">Status</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-muted-foreground">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="reg in recent_registrations" :key="reg.id" class="border-b last:border-0 hover:bg-muted/40">
                                <td class="px-3 py-2.5">
                                    <div class="font-medium text-sm">{{ reg.event?.title ?? '-' }}</div>
                                    <div class="text-xs text-muted-foreground">{{ reg.event?.location ?? '' }}</div>
                                </td>
                                <td class="px-3 py-2.5 text-sm">{{ reg.event?.discipline?.name ?? '-' }}</td>
                                <td class="px-3 py-2.5 text-sm">{{ formatDate(reg.event?.start_date) }}</td>
                                <td class="px-3 py-2.5">
                                    <Badge :class="statusColors[reg.status] || statusColors.draft">
                                        {{ reg.status_label }}
                                    </Badge>
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    <Link :href="route('rider.registrations.show', reg.id)">
                                        <Button variant="ghost" size="sm" class="h-8 gap-1">
                                            <Eye class="h-3.5 w-3.5" />
                                            Detail
                                        </Button>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-8">
                    <Layers class="mx-auto mb-2 h-10 w-10 opacity-40 text-muted-foreground" />
                    <h3 class="font-medium text-sm">Belum ada riwayat pendaftaran</h3>
                    <p class="text-xs text-muted-foreground mt-1">Daftarkan diri Anda di event pilihan untuk mulai berlaga.</p>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="text-base flex items-center gap-2">
                        <Calendar class="h-5 w-5 text-orange-500" />
                        Alur Pendaftaran Event
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm text-muted-foreground">
                    <div class="flex gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-orange-100 text-orange-600 dark:bg-orange-900 dark:text-orange-300 text-xs font-bold">1</span>
                        <div>
                            <p class="font-medium text-foreground">Pilih Event & Kelas</p>
                            <p class="text-xs">Buka halaman event, pilih kelas race yang Anda ikuti</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-orange-100 text-orange-600 dark:bg-orange-900 dark:text-orange-300 text-xs font-bold">2</span>
                        <div>
                            <p class="font-medium text-foreground">Lengkapi Data & Upload Bukti Bayar</p>
                            <p class="text-xs">Isi biodata rider, lalu upload bukti transfer pembayaran</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-orange-100 text-orange-600 dark:bg-orange-900 dark:text-orange-300 text-xs font-bold">3</span>
                        <div>
                            <p class="font-medium text-foreground">Verifikasi Admin</p>
                            <p class="text-xs">Admin EO akan mengecek pembayaran dan menyetujui</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-orange-100 text-orange-600 dark:bg-orange-900 dark:text-orange-300 text-xs font-bold">4</span>
                        <div>
                            <p class="font-medium text-foreground">Unduh Dokumen</p>
                            <p class="text-xs">Formulir registrasi dan kwitansi PDF siap diunduh</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base flex items-center gap-2">
                        <AlertCircle class="h-5 w-5 text-orange-500" />
                        Penting!
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm text-muted-foreground">
                    <ul class="space-y-2 list-disc pl-5">
                        <li>Pastikan data rider (NIK, nama) sesuai dengan KTP asli</li>
                        <li>Lisensi (KTA IOF/KIS IMI) harus aktif sebelum hari-H</li>
                        <li>Bawa bukti cetak formulir registrasi & kwitansi saat scrutineering</li>
                        <li>Gunakan halaman verifikasi dokumen bila diminta panitia</li>
                    </ul>
                    <div class="pt-4 border-t space-y-2">
                        <Link :href="route('settings.profile')">
                            <Button variant="outline" class="w-full">
                                Lengkapi Profil Rider
                            </Button>
                        </Link>
                        <Link :href="route('home')">
                            <Button class="w-full bg-orange-500 hover:bg-orange-600">
                                Daftar Event Sekarang
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

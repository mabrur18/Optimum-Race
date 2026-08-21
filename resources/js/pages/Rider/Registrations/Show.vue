<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { ChevronLeft, User, Calendar, Layers, DollarSign, Phone, Mail, MapPin, IdCard, Shield, Upload, FileCheck, AlertCircle, CheckCircle2 } from 'lucide-vue-next';

interface RegDetail {
    id: string;
    status: string;
    status_label: string;
    status_color: string;
    is_paid: boolean;
    can_generate_documents: boolean;
    total_amount: number;
    created_at: string;
    rider?: {
        id: string;
        full_name: string;
        jersey_name?: string | null;
        id_number?: string | null;
        phone?: string | null;
        email?: string | null;
        regency?: string | null;
        province?: string | null;
        licenses?: { id: string; type: string; number: string }[] | null;
    } | null;
    event?: {
        id: string;
        title: string;
        slug?: string;
        location?: string | null;
        start_date?: string | null;
        end_date?: string | null;
        eo_name?: string | null;
        discipline?: { id: string; name: string; organizer?: any } | null;
    } | null;
    items?: {
        id?: string;
        race_class?: { id: string; name: string; code: string; price?: number } | null;
        start_number?: string | null;
        is_main_class?: boolean;
    }[];
    payment_proofs?: {
        id: string;
        file_path?: string | null;
        amount?: number;
        note?: string | null;
        created_at?: string;
    }[] | null;
    documents?: any[] | null;
}

interface Props {
    registration: RegDetail;
}

const props = defineProps<Props>();

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (d?: string | null) => {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const formatDateTime = (d?: string | null) => {
    if (!d) return '-';
    return new Date(d).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
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
    <Head :title="'Detail Registrasi - ' + (registration.event?.title ?? registration.id)" />

    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <Link :href="route('rider.registrations.index')">
                <Button variant="outline" size="icon">
                    <ChevronLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-3xl font-bold tracking-tight truncate">
                        {{ registration.event?.title ?? 'Event Tidak Ditemukan' }}
                    </h1>
                    <Badge :class="statusColors[registration.status] || statusColors.draft">
                        {{ registration.status_label }}
                    </Badge>
                </div>
                <p class="text-muted-foreground">
                    ID Registrasi: <code class="rounded bg-muted px-2 py-0.5 text-xs">{{ registration.id.substring(0, 13) }}...</code>
                </p>
            </div>
        </div>

        <div v-if="registration.status === 'pending_payment'" class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 dark:border-yellow-800 dark:bg-yellow-900/20">
            <div class="flex items-start gap-3">
                <AlertCircle class="h-5 w-5 text-yellow-600 dark:text-yellow-400 shrink-0 mt-0.5" />
                <div class="text-sm text-yellow-800 dark:text-yellow-300 flex-1">
                    <p class="font-medium">Segera selesaikan pembayaran Anda.</p>
                    <p class="mt-0.5">Pendaftaran berstatus <span class="font-bold">Menunggu Pembayaran</span>. Silakan transfer sesuai nominal lalu hubungi admin event untuk upload bukti pembayaran.</p>
                </div>
                <div class="text-right shrink-0">
                    <div class="text-xs text-yellow-700 dark:text-yellow-400 mb-0.5">Total Tagihan</div>
                    <div class="text-xl font-black text-yellow-800 dark:text-yellow-300">{{ formatCurrency(registration.total_amount) }}</div>
                </div>
            </div>
        </div>
        <div v-else-if="registration.status === 'approved' || registration.status === 'entry_list'" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 dark:border-green-800 dark:bg-green-900/20">
            <div class="flex items-start gap-3">
                <CheckCircle2 class="h-5 w-5 text-green-600 dark:text-green-400 shrink-0 mt-0.5" />
                <div class="text-sm text-green-800 dark:text-green-300">
                    <p class="font-medium">Pendaftaran Anda telah disetujui!</p>
                    <p class="mt-0.5">Selamat berlaga. Jangan lupa unduh formulir registrasi dan bawa saat hari-H untuk proses scrutineering.</p>
                </div>
            </div>
        </div>
        <div v-else-if="registration.status === 'payment_review'" class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-800 dark:bg-blue-900/20">
            <div class="flex items-start gap-3">
                <FileCheck class="h-5 w-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
                <div class="text-sm text-blue-800 dark:text-blue-300">
                    <p class="font-medium">Bukti pembayaran sedang diverifikasi admin.</p>
                    <p class="mt-0.5">Proses verifikasi biasanya membutuhkan waktu maksimal 1x24 jam. Silakan cek kembali nanti.</p>
                </div>
            </div>
        </div>
        <div v-else-if="registration.status === 'rejected'" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 dark:border-red-800 dark:bg-red-900/20">
            <div class="flex items-start gap-3">
                <AlertCircle class="h-5 w-5 text-red-600 dark:text-red-400 shrink-0 mt-0.5" />
                <div class="text-sm text-red-800 dark:text-red-300">
                    <p class="font-medium">Pendaftaran Anda ditolak.</p>
                    <p class="mt-0.5">Silakan hubungi admin event untuk informasi lebih lanjut.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <User class="h-5 w-5 text-orange-500" />
                            Data Diri Rider
                        </CardTitle>
                        <CardDescription>Identitas yang tercatat pada sistem</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground mb-0.5">
                                    <IdCard class="h-3.5 w-3.5" /> Nama Lengkap (KTP)
                                </div>
                                <p class="font-medium">{{ registration.rider?.full_name ?? '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <div class="text-sm text-muted-foreground mb-0.5">Nama Jersey</div>
                                <p class="font-medium">{{ registration.rider?.jersey_name ?? '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground mb-0.5">
                                    <IdCard class="h-3.5 w-3.5" /> NIK / Nomor Identitas
                                </div>
                                <p class="font-mono text-sm">{{ registration.rider?.id_number ?? '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground mb-0.5">
                                    <Shield class="h-3.5 w-3.5" /> Jumlah Lisensi
                                </div>
                                <p class="font-medium">{{ registration.rider?.licenses?.length ?? 0 }} lisensi tercatat</p>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground mb-0.5">
                                    <Phone class="h-3.5 w-3.5" /> Telepon
                                </div>
                                <p class="font-medium">{{ registration.rider?.phone ?? '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground mb-0.5">
                                    <Mail class="h-3.5 w-3.5" /> Email
                                </div>
                                <p class="font-medium">{{ registration.rider?.email ?? '-' }}</p>
                            </div>
                            <div class="space-y-1 md:col-span-2">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground mb-0.5">
                                    <MapPin class="h-3.5 w-3.5" /> Domisili
                                </div>
                                <p class="font-medium">{{ registration.rider?.regency ?? '-' }}<template v-if="registration.rider?.province">, {{ registration.rider.province }}</template></p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Layers class="h-5 w-5 text-orange-500" />
                            Kelas Yang Diikuti ({{ registration.items?.length ?? 0 }})
                        </CardTitle>
                        <CardDescription>Kelas race yang Anda ikuti pada event ini</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-2 py-2 text-left text-xs font-medium text-muted-foreground">Kelas</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-muted-foreground">Kode</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-muted-foreground">No. Start</th>
                                    <th class="px-2 py-2 text-right text-xs font-medium text-muted-foreground">Biaya</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(it, i) in registration.items ?? []" :key="'it-' + i" class="border-b last:border-0">
                                    <td class="px-2 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium">{{ it.race_class?.name ?? '-' }}</span>
                                            <Badge v-if="it.is_main_class" variant="outline" class="text-[10px] border-orange-300 text-orange-700 dark:border-orange-700 dark:text-orange-400">UTAMA</Badge>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2.5">
                                        <code class="text-xs rounded bg-muted px-1.5 py-0.5">{{ it.race_class?.code ?? '-' }}</code>
                                    </td>
                                    <td class="px-2 py-2.5 text-sm">
                                        {{ it.start_number ?? '-' }}
                                    </td>
                                    <td class="px-2 py-2.5 text-right font-medium text-sm">
                                        {{ it.race_class?.price !== undefined && it.race_class?.price !== null ? formatCurrency(it.race_class.price) : '-' }}
                                    </td>
                                </tr>
                                <tr v-if="!registration.items || registration.items.length === 0">
                                    <td colspan="4" class="px-2 py-8 text-center text-sm text-muted-foreground italic">
                                        Tidak ada kelas.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2">
                                    <td colspan="3" class="px-2 py-3 text-right text-sm font-bold">TOTAL:</td>
                                    <td class="px-2 py-3 text-right font-black text-lg text-orange-600 dark:text-orange-500">
                                        {{ formatCurrency(registration.total_amount) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Upload class="h-5 w-5 text-orange-500" />
                            Bukti Pembayaran
                        </CardTitle>
                        <CardDescription>Riwayat upload bukti transfer Anda</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="registration.payment_proofs && registration.payment_proofs.length > 0" class="space-y-3">
                            <div
                                v-for="(proof, i) in registration.payment_proofs"
                                :key="'pf-' + i"
                                class="border rounded-lg p-4 bg-muted/30"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-sm">
                                            Bukti #{{ i + 1 }}
                                            <template v-if="proof.amount">
                                                - {{ formatCurrency(proof.amount) }}
                                            </template>
                                        </p>
                                        <p v-if="proof.created_at" class="text-xs text-muted-foreground mt-0.5">
                                            Diupload: {{ formatDateTime(proof.created_at) }}
                                        </p>
                                    </div>
                                </div>
                                <p v-if="proof.note" class="text-xs text-muted-foreground mt-2 italic">Catatan: {{ proof.note }}</p>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-sm text-muted-foreground">
                            <Upload class="mx-auto mb-2 h-10 w-10 opacity-50" />
                            <p>Belum ada bukti pembayaran yang diupload.</p>
                            <p class="mt-1 text-xs">Silakan transfer sesuai tagihan dan hubungi admin event untuk upload bukti.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Calendar class="h-5 w-5 text-orange-500" />
                            Ringkasan Event
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div class="font-semibold text-base">{{ registration.event?.title ?? '-' }}</div>
                        <Separator class="my-2" />
                        <div class="flex justify-between gap-2"><span class="text-muted-foreground">Penyelenggara:</span> <span class="font-medium text-right">{{ registration.event?.eo_name ?? '-' }}</span></div>
                        <div class="flex justify-between gap-2"><span class="text-muted-foreground">Disiplin:</span> <span class="font-medium text-right">{{ registration.event?.discipline?.name ?? '-' }}</span></div>
                        <div class="flex justify-between gap-2"><span class="text-muted-foreground">Lokasi:</span> <span class="font-medium text-right">{{ registration.event?.location ?? '-' }}</span></div>
                        <div class="flex justify-between gap-2"><span class="text-muted-foreground">Tanggal:</span> <span class="font-medium text-right">{{ formatDate(registration.event?.start_date) }}<template v-if="registration.event?.end_date && registration.event.end_date !== registration.event.start_date"> - {{ formatDate(registration.event.end_date) }}</template></span></div>
                        <Separator class="my-2" />
                        <div class="flex justify-between gap-2"><span class="text-muted-foreground">Waktu Daftar:</span> <span class="font-medium text-right">{{ formatDateTime(registration.created_at) }}</span></div>
                        <div class="flex justify-between gap-2 items-start pt-1"><span class="text-muted-foreground shrink-0">Status:</span> <Badge :class="statusColors[registration.status] || statusColors.draft" class="text-right">{{ registration.status_label }}</Badge></div>
                        <Separator class="my-2" />
                        <div class="flex justify-between gap-2"><span class="text-muted-foreground">Lunas:</span> <span class="font-medium text-right">{{ registration.is_paid ? '✅ Ya' : '⏳ Belum' }}</span></div>
                        <div class="flex justify-between gap-2"><span class="text-muted-foreground">Dokumen PDF:</span> <span class="font-medium text-right">{{ registration.can_generate_documents ? '✅ Siap' : '🔒 Menunggu' }}</span></div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <DollarSign class="h-5 w-5 text-orange-500" />
                            Tagihan & Aksi
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="bg-muted/40 rounded-lg p-4 text-center">
                            <div class="text-xs text-muted-foreground mb-1">Total Tagihan</div>
                            <div class="text-2xl font-black text-orange-600 dark:text-orange-500">{{ formatCurrency(registration.total_amount) }}</div>
                        </div>

                        <div v-if="registration.can_generate_documents" class="space-y-2">
                            <Button class="w-full bg-orange-500 hover:bg-orange-600" disabled>
                                <FileCheck class="mr-2 h-4 w-4" />
                                Unduh Formulir (Segera)
                            </Button>
                            <Button variant="outline" class="w-full" disabled>
                                Unduh Kwitansi (Segera)
                            </Button>
                        </div>
                        <div v-else class="space-y-2">
                            <Button class="w-full bg-orange-500 hover:bg-orange-600" disabled>
                                <FileCheck class="mr-2 h-4 w-4" />
                                {{ registration.is_paid ? 'Dokumen Sedang Diproses' : 'Selesaikan Pembayaran Dulu' }}
                            </Button>
                            <p class="text-xs text-center text-muted-foreground">Dokumen PDF dapat diunduh setelah status pendaftaran {{ statusColors.approved ? 'Disetujui' : 'Approved' }}.</p>
                        </div>

                        <Separator />

                        <Link v-if="registration.event?.slug" :href="route('events.show', registration.event.slug)">
                            <Button variant="ghost" class="w-full">
                                ← Kembali ke Halaman Event
                            </Button>
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>

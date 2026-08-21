<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm, usePage, Head, Link } from '@inertiajs/vue3';
import { Toaster, toast } from 'vue-sonner';
import route from '@/composables/useRouteHelper';
import {
    ArrowLeft,
    Banknote,
    CheckCircle2,
    CreditCard,
    FileUp,
    ShieldCheck,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';

type PageProps = {
    prefill_invoice: string;
    invoice?: {
        id: string;
        invoice_number: string;
        total: number;
        total_formatted: string;
        paid_at?: string | null;
        emailed_at?: string | null;
    } | null;
    registration?: {
        id: string;
        status: string;
        status_label: string;
        full_name?: string | null;
        team_name?: string | null;
        email?: string | null;
    } | null;
    event?: {
        slug: string;
        title: string;
    } | null;
    submitted?: boolean;
    flash?: {
        toast?: { type: string; message: string } | null;
        success?: string | null;
        error?: string | null;
    } | null;
};

const props = defineProps<PageProps>();
const page = usePage<any>();
const lastToastKey = ref<string>('');

watch(
    () => (page.props as any)?.flash?.toast as { type: string; message: string } | null,
    (t) => {
        if (!t?.type || !t?.message) return;
        const key = `${t.type}:${t.message}`;
        if (lastToastKey.value === key) return;
        lastToastKey.value = key;
        (toast as any)[t.type](t.message, { duration: 5000 });
    },
    { immediate: true }
);

const MAX_MB = 5;
const form = useForm({
    invoice_number: props.prefill_invoice ?? '',
    proof_file: null as File | null,
});

const proofFileInput = ref<HTMLInputElement | null>(null);
const openProofFilePicker = () => {
    if (proofFileInput.value) proofFileInput.value.click();
};

const onProofFileChange = (ev: Event) => {
    const target = ev.target as HTMLInputElement | null;
    if (!target || !target.files || target.files.length === 0) {
        form.proof_file = null;
        return;
    }
    form.proof_file = target.files[0];
};

const proofSizeKb = computed(() => form.proof_file ? Math.round((form.proof_file.size ?? 0) / 1024) : 0);
const proofSizeMb = computed(() => (proofSizeKb.value / 1024).toFixed(2));
const proofExt = computed(() => {
    if (!form.proof_file?.name) return '';
    return (form.proof_file.name.split('.').pop() ?? '').toLowerCase();
});
const proofValid = computed(() => {
    if (!form.proof_file) return false;
    if ((form.proof_file.size ?? 0) > MAX_MB * 1024 * 1024) return false;
    return ['pdf', 'jpg', 'jpeg', 'png'].includes(proofExt.value);
});
const proofName = computed(() => form.proof_file?.name ?? 'Belum ada file dipilih');

const invoiceNumberOk = computed(() => typeof form.invoice_number === 'string' && form.invoice_number.trim().length >= 6);

const submitDisabled = computed(() =>
    form.processing || !invoiceNumberOk.value || !proofValid.value
);

const submit = () => {
    form.post(route('payments.confirm.submit'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            toast.success('Bukti pembayaran berhasil dikirim.', { duration: 4000 });
        },
        onError: (errors) => {
            const first = Object.values(errors)[0];
            toast.error(typeof first === 'string' ? first : 'Periksa kembali isian Anda.', { duration: 4500 });
        },
    });
};
</script>

<template>
    <Toaster />
    <Head :title="'Konfirmasi Pembayaran | ORS'" />

    <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-950 dark:to-gray-900">
        <nav class="border-b bg-white/80 backdrop-blur-sm dark:bg-gray-950/80 sticky top-0 z-50">
            <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link :href="route('home')" class="flex items-center gap-2">
                    <ArrowLeft class="h-4 w-4 text-muted-foreground" />
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500 text-white">
                        <CreditCard class="h-4 w-4" />
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white">Kembali ke Halaman Utama</span>
                </Link>
                <Badge variant="outline" class="gap-1.5 border-emerald-300 text-emerald-700 dark:border-emerald-700 dark:text-emerald-400">
                    <ShieldCheck class="h-3.5 w-3.5" />
                    Pembayaran Aman
                </Badge>
            </div>
        </nav>

        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-6">
            <div class="text-center space-y-2">
                <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white">
                    Konfirmasi Pembayaran
                </h1>
                <p class="text-muted-foreground max-w-2xl mx-auto">
                    Masukkan nomor invoice yang Anda terima via email, lalu unggah bukti transfer bank agar tim admin dapat memproses verifikasi.
                </p>
            </div>

            <div v-if="event" class="flex items-center justify-center">
                <Link
                    v-if="event?.slug"
                    :href="route('events.show', event.slug)"
                    class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50/70 dark:bg-emerald-950/30 px-4 py-1.5 text-sm font-semibold text-emerald-700 dark:text-emerald-400 dark:border-emerald-900"
                >
                    {{ event.title }}
                </Link>
            </div>

            <div class="grid lg:grid-cols-5 gap-6">
                <Card class="lg:col-span-3 shadow-lg border-emerald-200/60 dark:border-emerald-900/60">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <Banknote class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                            Data Invoice & Bukti Transfer
                        </CardTitle>
                        <CardDescription>
                            Invoice diverifikasi otomatis oleh sistem. Pastikan nomor invoice sesuai dengan yang dikirim ke email Anda.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div v-if="props.submitted === true" class="rounded-xl border border-emerald-300 bg-emerald-50 dark:bg-emerald-950/30 dark:border-emerald-900 p-4">
                            <div class="flex items-start gap-3">
                                <CheckCircle2 class="h-6 w-6 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                                <div>
                                    <div class="font-semibold text-emerald-800 dark:text-emerald-300 text-sm">
                                        Bukti pembayaran TELAH DIKIRIM & invoice TERVERIFIKASI.
                                    </div>
                                    <div class="text-xs text-emerald-700/80 dark:text-emerald-400/80 mt-1 leading-relaxed">
                                        Silakan tunggu proses review admin maksimal 1x24 jam. Jika lolos verifikasi, Anda akan menerima email balasan dan status entry list otomatis terpublikasi.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="invoice_number">
                                Nomor Invoice
                                <span class="text-red-500 ml-1">*</span>
                            </Label>
                            <Input
                                id="invoice_number"
                                v-model="form.invoice_number"
                                placeholder="Contoh: ORS-202608-7A3B9K"
                                :error="form.errors.invoice_number"
                            />
                            <p v-if="!invoiceNumberOk" class="text-xs text-muted-foreground">
                                Minimal 6 karakter. Format: <strong>ORS-{Tahun}{Bulan}-{Kode acak}</strong>
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="proof_file">
                                Bukti Transfer Bank
                                <span class="text-red-500 ml-1">*</span>
                            </Label>
                            <div
                                class="rounded-xl border-2 border-dashed p-5 transition-colors hover:border-emerald-400"
                                :class="proofValid ? 'border-emerald-400 bg-emerald-50/30 dark:bg-emerald-950/20' : (form.proof_file ? 'border-red-400 bg-red-50/40 dark:bg-red-950/20' : 'border-gray-300 dark:border-gray-700')"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                                        <FileUp class="h-7 w-7" />
                                    </div>
                                    <div class="flex-1 min-w-0 space-y-1.5">
                                        <div class="text-sm font-medium truncate">{{ proofName }}</div>
                                        <div class="text-xs text-muted-foreground flex items-center gap-2">
                                            <span v-if="form.proof_file">Ukuran: <strong>{{ proofSizeMb }} MB</strong></span>
                                            <span v-else>Tidak ada file dipilih</span>
                                            <span class="opacity-60">• Maks {{ MAX_MB }} MB</span>
                                            <span v-if="form.proof_file" class="opacity-60">• Tipe: {{ proofExt.toUpperCase() }}</span>
                                        </div>
                                        <div class="text-xs font-medium" :class="proofValid ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">
                                            <template v-if="!form.proof_file">Menunggu file bukti transfer dipilih...</template>
                                            <template v-else-if="proofValid">✅ Format & ukuran file valid (PDF / JPG / PNG)</template>
                                            <template v-else>❌ Format atau ukuran file tidak sesuai. Hanya PDF, JPG, JPEG, PNG {{ MAX_MB }}MB.</template>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <input
                                            ref="proofFileInput"
                                            id="proof_file"
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                            class="sr-only"
                                            @change="onProofFileChange"
                                        />
                                        <Button type="button" variant="outline" size="sm" @click="openProofFilePicker">Pilih File</Button>
                                    </div>
                                </div>
                            </div>
                            <p v-if="form.errors.proof_file" class="text-sm text-red-500">{{ form.errors.proof_file }}</p>
                        </div>
                    </CardContent>
                    <CardFooter class="flex-col gap-3 border-t pt-5">
                        <Button
                            type="button"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 font-semibold py-6 text-base"
                            :disabled="submitDisabled"
                            @click="submit"
                        >
                            <CheckCircle2 class="mr-2 h-5 w-5" />
                            {{ form.processing ? 'Mengirim bukti pembayaran...' : 'KIRIM BUKTI PEMBAYARAN' }}
                        </Button>
                        <p class="text-[11px] text-muted-foreground text-center leading-relaxed">
                            Pastikan nominal transfer sesuai dengan total tagihan invoice. Data yang salah dapat memperlambat proses verifikasi.
                        </p>
                    </CardFooter>
                </Card>

                <div class="lg:col-span-2 space-y-6">
                    <Card class="shadow-md border-emerald-200/60 dark:border-emerald-900/60">
                        <CardHeader class="pb-3">
                            <CardTitle>Rincian Tagihan</CardTitle>
                            <CardDescription>
                                {{ invoice ? 'Invoice ditemukan di sistem ✓' : 'Masukkan nomor invoice untuk melihat rincian' }}
                            </CardDescription>
                        </CardHeader>
                        <Separator />
                        <CardContent class="pt-5 space-y-4 text-sm">
                            <template v-if="invoice">
                                <div class="flex justify-between items-center gap-3">
                                    <span class="text-muted-foreground">Nomor Invoice</span>
                                    <span class="font-bold tracking-tight">{{ invoice.invoice_number }}</span>
                                </div>
                                <Separator />
                                <div class="flex justify-between items-center gap-3">
                                    <span class="text-muted-foreground">Rider</span>
                                    <span class="font-medium text-right truncate">{{ registration?.full_name ?? '-' }}</span>
                                </div>
                                <div class="flex justify-between items-center gap-3">
                                    <span class="text-muted-foreground">Tim</span>
                                    <span class="text-right truncate">{{ registration?.team_name ?? '-' }}</span>
                                </div>
                                <div class="flex justify-between items-center gap-3">
                                    <span class="text-muted-foreground">Status Registrasi</span>
                                    <Badge :variant="registration?.status === 'entry_list' ? 'default' : 'outline'" class="text-xs">
                                        {{ registration?.status_label ?? '-' }}
                                    </Badge>
                                </div>
                                <Separator />
                                <div class="flex justify-between items-center pt-1">
                                    <span class="text-base font-bold">TOTAL BAYAR</span>
                                    <span class="text-2xl font-black text-emerald-600 dark:text-emerald-500">
                                        {{ invoice.total_formatted }}
                                    </span>
                                </div>
                            </template>
                            <template v-else>
                                <div class="text-center py-6 space-y-2 text-muted-foreground italic">
                                    <CreditCard class="mx-auto h-8 w-8 opacity-50" />
                                    <div>Belum ada data invoice.</div>
                                </div>
                            </template>
                        </CardContent>
                    </Card>

                    <Card class="shadow-md border-amber-200/60 dark:border-amber-900/60">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-base">Catatan Penting</CardTitle>
                        </CardHeader>
                        <CardContent class="pt-0 space-y-2 text-xs leading-relaxed text-muted-foreground">
                            <p>1. Invoice akan dikirim otomatis ke email maksimal 5 menit setelah pendaftaran awal berhasil.</p>
                            <p>2. Bukti transfer JPG/PNG/PDF hanya maksimal 5MB per file.</p>
                            <p>3. Proses approval admin hanya bisa berjalan JIKA nomor invoice VALID & bukti pembayaran TERVERIFIKASI sistem.</p>
                            <p>4. Status menjadi Entry List otomatis setelah Admin melakukan approval final (3 syarat: Invoice ✔️ + Bukti ✔️ + Cocok Nominal ✔️).</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </div>
</template>

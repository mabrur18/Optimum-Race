<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'vue-sonner';
import route from '@/composables/useRouteHelper';
import type { FlashToast } from '@/types/ui';
import {
    Mail,
    Lock,
    Server,
    KeyRound,
    Send,
    Save,
    CheckCircle2,
    XCircle,
    AlertTriangle,
    Shield,
    Globe,
    Eye,
    EyeOff,
    Loader2,
} from 'lucide-vue-next';

interface SmtpConfig {
    smtp_host: string;
    smtp_port: number;
    smtp_encryption: string;
    smtp_username: string;
    smtp_password: string;
    smtp_from_address: string;
    smtp_from_name: string;
    smtp_timeout: number;
}

interface Props {
    config: SmtpConfig;
    is_configured: boolean;
}

const props = defineProps<Props>();
const page = usePage<any>();
const lastShownToastKey = ref<string>('');

watch(
    () => (page.props as any)?.flash?.toast as FlashToast | undefined | null,
    (toastData) => {
        if (!toastData || !toastData.type || !toastData.message) return;
        const key = `${toastData.type}:${toastData.message}`;
        if (lastShownToastKey.value === key) return;
        lastShownToastKey.value = key;
        (toast as any)[toastData.type]?.(toastData.message);
    },
    { immediate: true }
);

const form = useForm<SmtpConfig>({
    smtp_host: props.config.smtp_host ?? '',
    smtp_port: Number(props.config.smtp_port ?? 587),
    smtp_encryption: (props.config.smtp_encryption as any) === '' ? 'none' : (props.config.smtp_encryption ?? 'tls'),
    smtp_username: props.config.smtp_username ?? '',
    smtp_password: props.config.smtp_password ?? '',
    smtp_from_address: props.config.smtp_from_address ?? '',
    smtp_from_name: props.config.smtp_from_name ?? '',
    smtp_timeout: Number(props.config.smtp_timeout ?? 30),
});

const isPasswordMasked = computed(() => {
    const p = form.smtp_password;
    return p !== '' && [...String(p)].every((c) => c === '•');
});

const showPassword = ref(false);

const onSubmitSave = () => {
    form.post(route('admin.settings.smtp.store'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Pengaturan SMTP berhasil disimpan.');
        },
        onError: (errors) => {
            console.error(errors);
            toast.error('Gagal menyimpan. Periksa kembali data yang dimasukkan.');
        },
    });
};

// === TEST KONEKSI MODAL ===
const testDialogOpen = ref(false);
const testRecipient = ref('');
const testLoading = ref(false);
const testResult = ref<{ success: boolean; message: string } | null>(null);

const openTestDialog = () => {
    testRecipient.value = form.smtp_from_address ? String(form.smtp_from_address) : '';
    testResult.value = null;
    testDialogOpen.value = true;
};

const runTestConnection = async () => {
    if (!testRecipient.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(testRecipient.value)) {
        toast.error('Masukkan alamat email penerima yang valid.');
        return;
    }
    if (!form.smtp_host || !form.smtp_port || !form.smtp_from_address || !form.smtp_from_name) {
        toast.error('Lengkapi dulu data host, port, email pengirim, dan nama pengirim.');
        return;
    }

    testLoading.value = true;
    testResult.value = null;
    try {
        const payload: Record<string, any> = {
            smtp_host: form.smtp_host,
            smtp_port: Number(form.smtp_port),
            smtp_encryption: form.smtp_encryption,
            smtp_username: form.smtp_username || null,
            smtp_password: isPasswordMasked.value ? null : (form.smtp_password || null),
            smtp_from_address: form.smtp_from_address,
            smtp_from_name: form.smtp_from_name,
            smtp_timeout: Number(form.smtp_timeout || 30),
            test_recipient: testRecipient.value,
        };

        const res = await fetch(route('admin.settings.smtp.test'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        let data: any = null;
        try {
            data = await res.json();
        } catch (_e) {
            /* ignore */
        }
        if (res.ok && data?.success) {
            testResult.value = { success: true, message: data.message || 'Email uji terkirim!' };
            toast.success('Uji koneksi berhasil!');
        } else {
            testResult.value = { success: false, message: data?.message || `Gagal (HTTP ${res.status}).` };
            toast.error('Uji koneksi gagal.');
        }
    } catch (e: any) {
        testResult.value = { success: false, message: 'Terjadi kesalahan jaringan: ' + (e?.message || String(e)) };
        toast.error('Kesalahan jaringan.');
    } finally {
        testLoading.value = false;
    }
};

// Helpers port reference
const portPresets = [
    { enc: 'tls', port: 587, label: 'STARTTLS 587 (umum)' },
    { enc: 'ssl', port: 465, label: 'SMTPS 465 (umum)' },
    { enc: 'none', port: 25, label: 'Plain 25 (lokal)' },
    { enc: 'none', port: 2525, label: 'Alternatif 2525' },
];
</script>

<template>
    <Toaster />
    <Head title="Pengaturan SMTP Email - Optimum Race Admin" />

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl flex items-center gap-2">
                    <Mail class="h-7 w-7 text-orange-500" />
                    Pengaturan SMTP Email
                </h1>
                <p class="mt-1 text-sm text-muted-foreground max-w-2xl leading-relaxed">
                    Konfigurasi server email untuk mengirimkan notifikasi invoice, verifikasi pendaftaran, dan komunikasi lain kepada peserta. Kredensial password disimpan dengan enkripsi AES via Laravel Crypt.
                </p>
            </div>
            <div>
                <Badge v-if="is_configured" class="bg-emerald-100 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/40 dark:text-emerald-300 border-0 gap-1.5">
                    <CheckCircle2 class="h-3.5 w-3.5" /> Terkonfigurasi
                </Badge>
                <Badge v-else variant="outline" class="gap-1.5">
                    <AlertTriangle class="h-3.5 w-3.5 text-amber-500" /> Belum Dikonfigurasi
                </Badge>
            </div>
        </div>

        <div v-if="page.props.flash?.success" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300">
            {{ page.props.flash.success }}
        </div>
        <div v-if="page.props.flash?.error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
            {{ page.props.flash.error }}
        </div>

        <form @submit.prevent="onSubmitSave">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Server class="h-5 w-5 text-orange-500" />
                        Konfigurasi Server
                    </CardTitle>
                    <CardDescription>
                        Dapatkan data ini dari penyedia layanan email Anda (Gmail SMTP, SendGrid, Mailgun, cPanel, dll).
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2 space-y-1">
                        <Label for="smtp_host" class="flex items-center gap-1.5">
                            <Globe class="h-3.5 w-3.5 text-muted-foreground" /> Host SMTP <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="smtp_host"
                            placeholder="contoh: smtp.gmail.com"
                            v-model="form.smtp_host"
                        />
                        <p v-if="form.errors.smtp_host" class="text-xs text-red-600">{{ form.errors.smtp_host }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="smtp_port" class="flex items-center gap-1.5">
                            <KeyRound class="h-3.5 w-3.5 text-muted-foreground" /> Port <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="smtp_port"
                            type="number"
                            min="1"
                            max="65535"
                            placeholder="587"
                            v-model.number="form.smtp_port"
                        />
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <button
                                v-for="p in portPresets"
                                :key="p.port + '-' + p.enc"
                                type="button"
                                class="text-[11px] rounded-full border px-2.5 py-1 hover:bg-orange-50 hover:text-orange-700 hover:border-orange-300 transition-colors"
                                @click="() => { form.smtp_port = p.port; form.smtp_encryption = p.enc; }"
                            >
                                {{ p.label }}
                            </button>
                        </div>
                        <p v-if="form.errors.smtp_port" class="text-xs text-red-600">{{ form.errors.smtp_port }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="smtp_encryption">Enkripsi Keamanan <span class="text-red-500">*</span></Label>
                        <Select
                            v-model="form.smtp_encryption"
                        >
                            <SelectTrigger id="smtp_encryption">
                                <SelectValue placeholder="Pilih enkripsi" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="tls">STARTTLS (TLS) — Port 587</SelectItem>
                                <SelectItem value="ssl">SSL / SMTPS — Port 465</SelectItem>
                                <SelectItem value="none">Tidak ada (Plain) — Port 25</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.smtp_encryption" class="text-xs text-red-600">{{ form.errors.smtp_encryption }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="smtp_username">Username Akun SMTP</Label>
                        <Input
                            id="smtp_username"
                            placeholder="seringkali sama dengan email pengirim"
                            autocomplete="off"
                            v-model="form.smtp_username"
                        />
                        <p v-if="form.errors.smtp_username" class="text-xs text-red-600">{{ form.errors.smtp_username }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="smtp_password" class="flex items-center gap-1.5">
                            <Lock class="h-3.5 w-3.5 text-muted-foreground" /> Password / App Password
                            <Badge variant="outline" class="ml-auto gap-1 text-[10px] px-2 py-0 border-emerald-300 text-emerald-700 dark:text-emerald-300 dark:border-emerald-700">
                                <Shield class="h-3 w-3" /> Terenkripsi
                            </Badge>
                        </Label>
                        <div class="relative">
                            <Input
                                id="smtp_password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="••••••••"
                                autocomplete="new-password"
                                v-model="form.smtp_password"
                            />
                            <button
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-muted-foreground hover:text-foreground"
                                @click="showPassword = !showPassword"
                                :title="showPassword ? 'Sembunyikan password' : 'Lihat password'"
                            >
                                <EyeOff v-if="showPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="isPasswordMasked" class="text-[11px] text-amber-600 dark:text-amber-400">
                            Password saat ini tersembunyi. Jika tidak diubah, password yang tersimpan akan tetap dipakai.
                        </p>
                        <p v-if="form.errors.smtp_password" class="text-xs text-red-600">{{ form.errors.smtp_password }}</p>
                    </div>
                </CardContent>

                <Separator />

                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Send class="h-5 w-5 text-orange-500" />
                        Identitas Pengirim
                    </CardTitle>
                    <CardDescription>
                        Data "From:" yang akan terlihat di kotak masuk peserta.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-1">
                        <Label for="smtp_from_address">Alamat Email Pengirim <span class="text-red-500">*</span></Label>
                        <Input
                            id="smtp_from_address"
                            type="email"
                            placeholder="noreply@optimumrace.com"
                            v-model="form.smtp_from_address"
                        />
                        <p v-if="form.errors.smtp_from_address" class="text-xs text-red-600">{{ form.errors.smtp_from_address }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="smtp_from_name">Nama Tampilan Pengirim <span class="text-red-500">*</span></Label>
                        <Input
                            id="smtp_from_name"
                            placeholder="Optimum Race System"
                            v-model="form.smtp_from_name"
                        />
                        <p v-if="form.errors.smtp_from_name" class="text-xs text-red-600">{{ form.errors.smtp_from_name }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="smtp_timeout">Timeout Koneksi (detik)</Label>
                        <Input
                            id="smtp_timeout"
                            type="number"
                            min="5"
                            max="300"
                            placeholder="30"
                            v-model.number="form.smtp_timeout"
                        />
                        <p v-if="form.errors.smtp_timeout" class="text-xs text-red-600">{{ form.errors.smtp_timeout }}</p>
                    </div>
                </CardContent>

                <CardFooter class="flex flex-col-reverse gap-3 border-t bg-muted/30 sm:flex-row sm:justify-between sm:items-center rounded-b-lg px-6 py-4">
                    <div class="text-xs text-muted-foreground">
                        💡 Setelah disimpan, invoice otomatis akan menggunakan pengaturan ini saat dikirim via queue.
                    </div>
                    <div class="flex gap-2 w-full sm:w-auto">
                        <Button type="button" variant="outline" class="flex-1 sm:flex-none gap-1.5" @click="openTestDialog" :disabled="form.processing">
                            <Send class="h-4 w-4" /> Uji Koneksi &amp; Kirim Email Uji
                        </Button>
                        <Button type="submit" class="flex-1 sm:flex-none gap-1.5 bg-orange-500 hover:bg-orange-600" :disabled="form.processing">
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <Save v-else class="h-4 w-4" />
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                        </Button>
                    </div>
                </CardFooter>
            </Card>
        </form>
    </div>

    <!-- Dialog Uji Koneksi -->
    <Dialog v-model="testDialogOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Send class="h-5 w-5 text-orange-500" />
                    Uji Koneksi SMTP &amp; Kirim Email Uji
                </DialogTitle>
                <DialogDescription>
                    Pengaturan yang Anda isi di atas akan digunakan sementara untuk mengirim email. Pastikan data sudah benar.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="space-y-1.5">
                    <Label for="test_recipient">Alamat Email Penerima Uji <span class="text-red-500">*</span></Label>
                    <Input
                        id="test_recipient"
                        type="email"
                        placeholder="contoh@email-anda.com"
                        v-model="testRecipient"
                    />
                </div>

                <div class="rounded-lg border bg-muted/40 p-3 text-xs space-y-1.5 font-mono">
                    <div class="flex"><span class="w-28 text-muted-foreground shrink-0">Host:</span><span>{{ form.smtp_host || '-' }}</span></div>
                    <div class="flex"><span class="w-28 text-muted-foreground shrink-0">Port:</span><span>{{ form.smtp_port || '-' }}</span></div>
                    <div class="flex"><span class="w-28 text-muted-foreground shrink-0">Enkripsi:</span><span>{{ form.smtp_encryption || '-' }}</span></div>
                    <div class="flex"><span class="w-28 text-muted-foreground shrink-0">Pengirim:</span><span>{{ form.smtp_from_name }} &lt;{{ form.smtp_from_address }}&gt;</span></div>
                </div>

                <Alert v-if="testResult" :variant="testResult.success ? 'default' : 'destructive'">
                    <CheckCircle2 v-if="testResult.success" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    <XCircle v-else class="h-4 w-4" />
                    <AlertTitle class="text-sm">{{ testResult.success ? 'Berhasil!' : 'Gagal!' }}</AlertTitle>
                    <AlertDescription class="text-xs leading-relaxed pt-1 whitespace-pre-wrap">{{ testResult.message }}</AlertDescription>
                </Alert>
            </div>

            <DialogFooter class="flex-col sm:flex-row gap-2">
                <Button type="button" variant="outline" @click="testDialogOpen = false" :disabled="testLoading">
                    Tutup
                </Button>
                <Button type="button" class="bg-orange-500 hover:bg-orange-600 gap-1.5" @click="runTestConnection" :disabled="testLoading">
                    <Loader2 v-if="testLoading" class="h-4 w-4 animate-spin" />
                    <Send v-else class="h-4 w-4" />
                    {{ testLoading ? 'Mengirim...' : 'Kirim Email Uji Sekarang' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

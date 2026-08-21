<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { route } from '@/composables/useRouteHelper';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import { Input } from '@/components/ui/input';
import { ChevronLeft, User, Calendar, Layers, Phone, Mail, MapPin, IdCard, Shield, FileSignature, Upload, CheckCircle2, XCircle, Clock, FileCheck, History, Receipt, FileUp, Loader2, FileText, Sparkles } from 'lucide-vue-next';

interface PaymentProof {
    id: string;
    file_path?: string | null;
    amount?: number;
    note?: string | null;
    invoice_number?: string | null;
    verification_status?: string | null;
    verified_at?: string | null;
    rejection_reason?: string | null;
    uploaded_at?: string | null;
    created_at?: string | null;
}

interface RegDetail {
    id: string;
    status: string;
    status_label: string;
    status_color: string;
    is_paid: boolean;
    can_generate_documents: boolean;
    total_amount: number;
    created_at: string;
    approved_at?: string | null;
    approved_by?: string | null;
    entry_list_published_at?: string | null;
    start_number_main?: string | null;
    team_name?: string | null;
    full_name?: string | null;
    phone?: string | null;
    email?: string | null;
    regency?: string | null;
    province?: string | null;
    rider?: {
        id: string;
        full_name: string;
        jersey_name?: string | null;
        id_number?: string | null;
        phone?: string | null;
        email?: string | null;
        regency?: string | null;
        province?: string | null;
        team_name?: string | null;
        licenses?: { id: string; type: string; number: string }[] | null;
        user?: { id: string; email?: string | null } | null;
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
    payment_proofs?: PaymentProof[] | null;
    invoice?: {
        id: string;
        invoice_number: string;
        total: number;
        paid_at?: string | null;
        emailed_at?: string | null;
        created_at?: string | null;
    } | null;
    status_logs?: {
        id: string;
        from_status?: string | null;
        to_status?: string | null;
        note?: string | null;
        changed_by?: string | null;
        changer?: { id: string; name?: string | null; email?: string | null } | null;
        created_at?: string | null;
    }[] | null;
    documents?: any[] | null;
}

interface ApprovalChecks {
    invoice_exists: boolean;
    invoice_number?: string | null;
    invoice_emailed: boolean;
    any_proof_submitted: boolean;
    proof_approved: boolean;
    proof_uploaded_at?: string | null;
    proof_verified_at?: string | null;
    can_be_approved: boolean;
}

interface Props {
    registration: RegDetail;
    statuses_list: { value: string; label: string }[];
    approval_checks: ApprovalChecks;
}

const props = defineProps<Props>();

const page = usePage<any>();
const flash = page.props.flash as any;

type TabName = 'profil' | 'kelas' | 'dokumen' | 'pembayaran' | 'log';
const activeTab = ref<TabName>('profil');
watch(activeTab, (nv) => {
    console.log('[Show Registration] Active tab changed to:', nv);
});

const goToTab = async (tab: TabName, scrollAnchorId?: string, pulseElementId?: string) => {
    activeTab.value = tab;
    await nextTick();
    let target = document.getElementById('tabs-top-anchor') as HTMLElement | null;
    if (scrollAnchorId) {
        const el = document.getElementById(scrollAnchorId);
        if (el) target = el;
    }
    if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    if (pulseElementId) {
        const pulseEl = document.getElementById(pulseElementId);
        if (pulseEl) {
            pulseEl.classList.add('!border-4', '!border-blue-500', 'ring-4', 'ring-blue-200', 'dark:ring-blue-800', 'animate-pulse');
            setTimeout(() => {
                pulseEl.classList.remove('!border-4', '!border-blue-500', 'ring-4', 'ring-blue-200', 'dark:ring-blue-800', 'animate-pulse');
            }, 2800);
        }
    }
};

const form = useForm({
    status: props.registration.status,
    note: '',
    proof_id: '' as string,
    verification_status: '' as '' | 'pending' | 'approved' | 'rejected',
    rejection_reason: '',
});

const activeProofId = ref<string>('');
const activeVerificationStatus = ref<'pending' | 'approved' | 'rejected' | ''>('');
const activeRejectionReason = ref('');

const statusBadgeClass = (st?: string | null): string => {
    if (st === 'approved') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-200';
    if (st === 'rejected') return 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 border-red-200';
    return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 border-amber-200';
};

const canClickSubmit = computed(() => {
    if (form.processing) return false;
    return true;
});

const selectedApprovalScore = computed(() => {
    let score = 0;
    if (props.approval_checks.invoice_exists) score++;
    if (props.approval_checks.any_proof_submitted) score++;
    if (props.approval_checks.proof_approved) score++;
    if (activeVerificationStatus.value === 'approved' && activeProofId.value) score++;
    return Math.min(score, 3);
});

const MAX_FILE_MB = 5;
const allowedExtsAdmin = ['pdf', 'jpg', 'jpeg', 'png'];
const extOf = (name: string) => (name.split('.').pop() || '').toLowerCase();

const proofFileInput = ref<HTMLInputElement | null>(null);
const openProofFilePicker = () => {
    if (proofFileInput.value) proofFileInput.value.click();
};

const paymentForm = useForm({
    proof_file: null as File | null,
    note: '',
});

const proofFileName = computed(() => (paymentForm.proof_file as File | null)?.name ?? '');
const proofFileSizeMb = computed(() => {
    const f = paymentForm.proof_file as File | null;
    return f ? Number((f.size / 1024 / 1024).toFixed(2)) : 0;
});
const proofFileValid = computed(() => {
    const f = paymentForm.proof_file as File | null;
    if (!f) return false;
    if (proofFileSizeMb.value > MAX_FILE_MB) return false;
    return allowedExtsAdmin.includes(extOf(f.name));
});
const proofFileStatusColor = computed(() => {
    const f = paymentForm.proof_file as File | null;
    if (!f) return 'border-dashed border-slate-300 bg-slate-50/50 dark:border-slate-700 dark:bg-slate-900/20';
    if (proofFileValid.value) return 'border-emerald-400 bg-emerald-50/70 dark:border-emerald-700 dark:bg-emerald-950/30';
    return 'border-red-400 bg-red-50/70 dark:border-red-700 dark:bg-red-950/30';
});

const submitPaymentProof = () => {
    if (!proofFileValid.value || paymentForm.processing) return;
    paymentForm.post(
        route('admin.registrations.manual-payment-proof', props.registration.id),
        {
            forceFormData: true,
            onSuccess: () => {
                toast.success('Bukti pembayaran berhasil diunggah.', { duration: 2500 });
                paymentForm.reset();
                if (proofFileInput.value) proofFileInput.value.value = '';
            },
            onError: (errors) => {
                const first = Object.values(errors)[0];
                toast.error(typeof first === 'string' ? first : 'Gagal mengunggah bukti pembayaran.');
            },
        }
    );
};

const docFileInput = ref<HTMLInputElement | null>(null);
const openDocFilePicker = () => {
    if (docFileInput.value) docFileInput.value.click();
};

const documentForm = useForm({
    document_type: 'kis' as 'kis' | 'kta_iof' | 'surat_rekomendasi',
    document_file: null as File | null,
    note: '',
});

const documentFileName = computed(() => (documentForm.document_file as File | null)?.name ?? '');
const documentFileSizeMb = computed(() => {
    const f = documentForm.document_file as File | null;
    return f ? Number((f.size / 1024 / 1024).toFixed(2)) : 0;
});
const documentFileValid = computed(() => {
    const f = documentForm.document_file as File | null;
    if (!f) return false;
    if (documentFileSizeMb.value > MAX_FILE_MB) return false;
    return allowedExtsAdmin.includes(extOf(f.name));
});
const documentFileStatusColor = computed(() => {
    const f = documentForm.document_file as File | null;
    if (!f) return 'border-dashed border-slate-300 bg-slate-50/50 dark:border-slate-700 dark:bg-slate-900/20';
    if (documentFileValid.value) return 'border-emerald-400 bg-emerald-50/70 dark:border-emerald-700 dark:bg-emerald-950/30';
    return 'border-red-400 bg-red-50/70 dark:border-red-700 dark:bg-red-950/30';
});

const submitDocument = () => {
    if (!documentFileValid.value || documentForm.processing) return;
    documentForm.post(
        route('admin.registrations.manual-document', props.registration.id),
        {
            forceFormData: true,
            onSuccess: () => {
                toast.success('Dokumen rider berhasil diunggah.', { duration: 2500 });
                documentForm.reset('document_file', 'note');
                documentForm.document_type = 'kis';
                if (docFileInput.value) docFileInput.value.value = '';
            },
            onError: (errors) => {
                const first = Object.values(errors)[0];
                toast.error(typeof first === 'string' ? first : 'Gagal mengunggah dokumen rider.');
            },
        }
    );
};

const generatingInvoice = computed<boolean>(() => generateInvoiceForm.processing);
const generateInvoiceForm = useForm({});
const generateManualInvoice = () => {
    if (generatingInvoice.value) return;
    const url = route('admin.registrations.generate-invoice', props.registration.id);
    generateInvoiceForm.post(url, {
        onSuccess: async () => {
            toast.success('Invoice berhasil dibuat secara manual! Pindah ke Tab Invoice & Bukti...', { duration: 2800 });
            await goToTab('pembayaran', 'invoice-detail-row', 'invoice-detail-row');
        },
        onError: (errors) => {
            const first = Object.values(errors)[0];
            toast.error(typeof first === 'string' ? first : 'Gagal generate invoice.');
        },
        preserveScroll: true,
    });
};

const quickApproveProofAndGoto = async () => {
    if (!props.registration.payment_proofs || props.registration.payment_proofs.length === 0) {
        toast.warning('Belum ada bukti transfer yang diupload peserta.', { duration: 2500 });
        await goToTab('pembayaran', 'upload-proof-row');
        return;
    }
    const lastProof = [...props.registration.payment_proofs].sort(
        (a, b) => new Date(b.created_at ?? b.uploaded_at ?? 0).getTime() - new Date(a.created_at ?? a.uploaded_at ?? 0).getTime()
    )[0];
    activeProofId.value = lastProof.id;
    activeVerificationStatus.value = 'approved';
    toast('Verifikasi bukti preset: ✅ SETUJUI. Silakan pilih status Disetujui lalu klik Simpan Perubahan.', { duration: 3200 });
    await goToTab('pembayaran', `proof-row-${lastProof.id}`, `proof-row-${lastProof.id}`);
};

const documentTypeLabel = (t?: string | null): string => {
    const map: Record<string, string> = {
        'kis': 'Kartu Identitas Sepeda (KIS)',
        'kta_iof': 'KTA IOF',
        'surat_rekomendasi': 'Surat Rekomendasi',
        'registration_form': 'Formulir Registrasi',
        'receipt': 'Kwitansi',
    };
    return map[t ?? ''] ?? (t ? String(t).toUpperCase() : '-');
};
const documentBadgeColor = (t?: string | null): string => {
    if (t === 'kis') return 'bg-blue-100 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300 border-blue-200';
    if (t === 'kta_iof') return 'bg-purple-100 text-purple-700 dark:bg-purple-950/30 dark:text-purple-300 border-purple-200';
    if (t === 'surat_rekomendasi') return 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300 border-amber-200';
    return 'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-300';
};

const updateStatus = () => {
    form.proof_id = activeProofId.value;
    form.verification_status = activeVerificationStatus.value;
    form.rejection_reason = activeRejectionReason.value;

    form.post(route('admin.registrations.update-status', props.registration.id), {
        onSuccess: () => {
            toast.success('Status pendaftaran berhasil diperbarui!', { duration: 2500 });
            form.note = '';
            activeProofId.value = '';
            activeVerificationStatus.value = '';
            activeRejectionReason.value = '';
        },
        onError: (errors) => {
            const first = Object.values(errors)[0];
            toast.error(typeof first === 'string' ? first : 'Gagal memperbarui status pendaftaran.');
        },
    });
};

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
    return new Date(d).toLocaleString('id-ID');
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

const statusLabelsMap: Record<string, string> = {
    draft: 'Draft',
    pending_payment: 'Menunggu Pembayaran',
    payment_review: 'Review Bukti',
    rejected: 'Ditolak',
    approved: 'Disetujui',
    entry_list: 'Entry List (Publik)',
    cancelled: 'Dibatalkan',
};
</script>

<template>
    <Head :title="'Registrasi - ' + (registration.rider?.full_name ?? registration.full_name ?? registration.id)" />

    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <Link :href="route('admin.registrations.index')">
                <Button variant="outline" size="icon">
                    <ChevronLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-3xl font-bold tracking-tight truncate">
                        {{ registration.rider?.full_name ?? registration.full_name ?? 'Peserta Tidak Diketahui' }}
                    </h1>
                    <Badge :class="statusColors[registration.status] || statusColors.draft">
                        {{ registration.status_label }}
                    </Badge>
                </div>
                <p class="text-muted-foreground">
                    {{ registration.event?.title ?? 'Event tidak ditemukan' }}
                </p>
            </div>
            <Link v-if="registration.event?.slug" :href="route('events.show', registration.event.slug)" target="_blank">
                <Button variant="outline">Lihat Publik ↗</Button>
            </Link>
        </div>

        <div v-if="flash?.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
            {{ flash.success }}
        </div>
        <div v-if="flash?.error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
            {{ flash.error }}
        </div>
        <div v-if="flash?.errors && flash.errors.status" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
            <strong>Tidak bisa Approve / Entry List:</strong> {{ flash.errors.status }}
        </div>

        <Card :class="approval_checks.can_be_approved ? 'border-emerald-300 bg-emerald-50/50 dark:bg-emerald-950/10' : 'border-red-300 bg-red-50/60 dark:bg-red-950/10'">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-lg">
                    <FileCheck class="h-5 w-5" />
                    Checklist Validasi Approval (3 Syarat Wajib)
                </CardTitle>
                <CardDescription>
                    Persetujuan <strong>Approved / Entry List</strong> hanya bisa dilakukan jika checklist di bawah ini SEMUA terpenuhi (hard-gating di backend).
                </CardDescription>
            </CardHeader>
            <CardContent class="grid md:grid-cols-3 gap-4">
                <div class="rounded-lg border bg-white dark:bg-gray-900 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <CheckCircle2 v-if="approval_checks.invoice_exists" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        <XCircle v-else class="h-5 w-5 text-red-500" />
                        <div class="text-sm font-semibold">1. Invoice Telah Dibuat</div>
                    </div>
                    <div class="text-xs text-muted-foreground space-y-0.5">
                        <div>No. Invoice: <span class="font-medium text-gray-900 dark:text-white">{{ approval_checks.invoice_number ?? registration.invoice?.invoice_number ?? '-' }}</span></div>
                        <div>Dibuat: {{ formatDateTime(registration.invoice?.created_at) }}</div>
                        <div>Total: {{ registration.invoice?.total !== undefined && registration.invoice.total !== null ? formatCurrency(Number(registration.invoice.total)) : '-' }}</div>
                        <div>Email terkirim: <span :class="approval_checks.invoice_emailed ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-amber-600 font-semibold'">{{ approval_checks.invoice_emailed ? 'Ya' : 'Belum' }}</span> ({{ formatDateTime(registration.invoice?.emailed_at) }})</div>
                        <div v-if="!approval_checks.invoice_exists" class="pt-2">
                            <Button
                                type="button"
                                size="sm"
                                class="w-full bg-gradient-to-br from-indigo-500 to-violet-600 hover:from-indigo-600 hover:to-violet-700 font-semibold"
                                :disabled="generatingInvoice"
                                @click="generateManualInvoice"
                            >
                                <Sparkles v-if="!generatingInvoice" class="mr-1.5 h-3.5 w-3.5" />
                                <Loader2 v-else class="mr-1.5 h-3.5 w-3.5 animate-spin" />
                                {{ generatingInvoice ? 'Sedang membuat invoice...' : '✨ BUAT INVOICE SECARA MANUAL' }}
                            </Button>
                            <p class="text-[10.5px] mt-1.5 text-indigo-700 dark:text-indigo-400 font-medium leading-tight">
                                Klik tombol ini untuk generate invoice pendaftaran secara otomatis beserta nomor unik ORS.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border bg-white dark:bg-gray-900 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <CheckCircle2 v-if="approval_checks.any_proof_submitted" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        <Clock v-else class="h-5 w-5 text-amber-500" />
                        <div class="text-sm font-semibold">2. Bukti Transfer Diupload</div>
                    </div>
                    <div class="text-xs text-muted-foreground space-y-0.5">
                        <div>Total bukti: <span class="font-medium text-gray-900 dark:text-white">{{ registration.payment_proofs?.length ?? 0 }} file</span></div>
                        <div>Upload terakhir: {{ formatDateTime(approval_checks.proof_uploaded_at) }}</div>
                        <div class="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="w-full border-blue-300 text-blue-700 hover:bg-blue-50 dark:border-blue-800 dark:text-blue-300 dark:hover:bg-blue-950/30"
                                @click="goToTab('pembayaran', 'upload-proof-row')"
                            >
                                <Receipt class="mr-1.5 h-3.5 w-3.5" />
                                {{ approval_checks.any_proof_submitted ? '🔽 LIHAT BUKTI DI TAB INVOICE' : '🔽 UNGGAH BUKTI MANUAL DI TAB INVOICE' }}
                            </Button>
                            <p v-if="!approval_checks.any_proof_submitted" class="text-[10.5px] mt-1.5 text-blue-700 dark:text-blue-400 font-medium leading-tight">
                                Jika user mengirim bukti via WhatsApp, buka tab di atas lalu klik Unggah Bukti Manual.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border bg-white dark:bg-gray-900 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <CheckCircle2 v-if="approval_checks.proof_approved" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        <XCircle v-else class="h-5 w-5 text-red-500" />
                        <div class="text-sm font-semibold">3. Bukti Sudah Approved</div>
                    </div>
                    <div class="text-xs text-muted-foreground space-y-0.5">
                        <div>Verifikasi Admin: <span :class="approval_checks.proof_approved ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-red-600 font-semibold'">{{ approval_checks.proof_approved ? 'SUDAH ✓' : 'BELUM ✓' }}</span></div>
                        <div>Timestamp verifikasi: {{ formatDateTime(approval_checks.proof_verified_at) }}</div>
                        <div v-if="!approval_checks.proof_approved" class="pt-2 space-y-2">
                            <Button
                                type="button"
                                size="sm"
                                class="w-full bg-gradient-to-br from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-semibold"
                                @click="quickApproveProofAndGoto"
                            >
                                <CheckCircle2 class="mr-1.5 h-3.5 w-3.5" />
                                🔥 SEKALI KLIK: PRESET SETUJUI + PINDAH TAB
                            </Button>
                            <p class="text-[10.5px] text-orange-700 dark:text-orange-400 font-medium leading-tight">
                                Pindah otomatis ke Tab Invoice, preset verifikasi = ✅ SETUJUI. Anda tinggal pilih status Disetujui lalu klik Simpan.
                            </p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card class="border-orange-200 bg-orange-50/40 dark:border-orange-900/40 dark:bg-orange-950/10">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-lg">
                    <Upload class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                    Unggah Manual Dokumen (Admin Only)
                </CardTitle>
                <CardDescription>
                    Kedua jenis dokumen ini bersifat <strong>opsional / non-wajib</strong>. Dapat diunggah oleh admin kapan saja tanpa mengganggu alur status pembayaran yang ada.
                </CardDescription>
            </CardHeader>
            <CardContent class="grid gap-6 md:grid-cols-2">
                <Card id="upload-proof-row" class="border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Receipt class="h-4 w-4 text-green-600 dark:text-green-400" />
                            1. Unggah Bukti Transfer Pembayaran (Manual)
                        </CardTitle>
                        <CardDescription>Admin bisa mengunggah bukti transfer dari WA/email peserta. Ukuran maks 5 MB (PDF/JPG/JPEG/PNG).</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div :class="['rounded-xl border-2 p-4 min-h-[140px] transition-colors', proofFileStatusColor]">
                            <input
                                ref="proofFileInput"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                class="sr-only"
                                @change="(e) => { const t = (e as Event).target as HTMLInputElement | null; paymentForm.proof_file = t && t.files && t.files[0] ? t.files[0] : null; }"
                            />
                            <div class="flex items-start gap-3 h-full">
                                <div class="shrink-0 flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                    <FileUp class="h-5 w-5 text-slate-500 dark:text-slate-300" />
                                </div>
                                <div class="flex-1 space-y-1 min-w-0">
                                    <div class="text-sm font-semibold">
                                        {{ proofFileName || 'Belum ada file dipilih' }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        <template v-if="proofFileName">
                                            Ukuran: {{ proofFileSizeMb }} MB • Tipe: {{ extOf(proofFileName).toUpperCase() }}
                                        </template>
                                        <template v-else>
                                            Pilih file bukti transfer bank
                                        </template>
                                    </div>
                                    <div v-if="paymentForm.proof_file && !proofFileValid" class="text-xs font-medium text-red-600 dark:text-red-400 pt-0.5">
                                        ⚠️ Format / ukuran file tidak sesuai (maks 5 MB • PDF/JPG/JPEG/PNG).
                                    </div>
                                </div>
                                <div class="shrink-0">
                                    <Button type="button" variant="outline" size="sm" @click="openProofFilePicker">Pilih File</Button>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="admin_payment_note">Catatan (opsional)</Label>
                            <Input id="admin_payment_note" v-model="paymentForm.note" placeholder="Contoh: Transfer via BRI tanggal 19/08 dari rekening a.n. Ali Mabrur" />
                        </div>
                        <Button
                            type="button"
                            class="w-full"
                            :disabled="paymentForm.processing || !proofFileValid"
                            @click="submitPaymentProof"
                        >
                            <Loader2 v-if="paymentForm.processing" class="h-4 w-4 mr-2 animate-spin" />
                            <Upload v-else class="h-4 w-4 mr-2" />
                            {{ paymentForm.processing ? 'Mengunggah...' : 'Unggah Bukti Pembayaran' }}
                        </Button>
                    </CardContent>
                </Card>

                <Card class="border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <FileCheck class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            2. Unggah Dokumen Rider (KIS / KTA IOF / Surat Rekomendasi)
                        </CardTitle>
                        <CardDescription>Admin bisa menambahkan dokumen pendukung rider secara manual. Bersifat non-mandatory. Ukuran maks 5 MB (PDF/JPG/JPEG/PNG).</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="space-y-1.5">
                            <Label for="admin_doc_type">Jenis Dokumen</Label>
                            <Select v-model="documentForm.document_type">
                                <SelectTrigger id="admin_doc_type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="kis">KIS (Kartu Identitas Sepeda)</SelectItem>
                                    <SelectItem value="kta_iof">KTA IOF (Anggota IOF)</SelectItem>
                                    <SelectItem value="surat_rekomendasi">Surat Rekomendasi Klub / Official</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div :class="['rounded-xl border-2 p-4 min-h-[140px] transition-colors', documentFileStatusColor]">
                            <input
                                ref="docFileInput"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                class="sr-only"
                                @change="(e) => { const t = (e as Event).target as HTMLInputElement | null; documentForm.document_file = t && t.files && t.files[0] ? t.files[0] : null; }"
                            />
                            <div class="flex items-start gap-3 h-full">
                                <div class="shrink-0 flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                    <FileUp class="h-5 w-5 text-slate-500 dark:text-slate-300" />
                                </div>
                                <div class="flex-1 space-y-1 min-w-0">
                                    <div class="text-sm font-semibold">
                                        {{ documentFileName || 'Belum ada file dipilih' }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        <template v-if="documentFileName">
                                            Ukuran: {{ documentFileSizeMb }} MB • Tipe: {{ extOf(documentFileName).toUpperCase() }}
                                        </template>
                                        <template v-else>
                                            Pilih file dokumen pendukung rider
                                        </template>
                                    </div>
                                    <div v-if="documentForm.document_file && !documentFileValid" class="text-xs font-medium text-red-600 dark:text-red-400 pt-0.5">
                                        ⚠️ Format / ukuran file tidak sesuai (maks 5 MB • PDF/JPG/JPEG/PNG).
                                    </div>
                                </div>
                                <div class="shrink-0">
                                    <Button type="button" variant="outline" size="sm" @click="openDocFilePicker">Pilih File</Button>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="admin_doc_note">Catatan (opsional)</Label>
                            <Input id="admin_doc_note" v-model="documentForm.note" placeholder="Contoh: Diterima via WhatsApp panitia teknis 19/08" />
                        </div>
                        <Button
                            type="button"
                            variant="secondary"
                            class="w-full"
                            :disabled="documentForm.processing || !documentFileValid"
                            @click="submitDocument"
                        >
                            <Loader2 v-if="documentForm.processing" class="h-4 w-4 mr-2 animate-spin" />
                            <Upload v-else class="h-4 w-4 mr-2" />
                            {{ documentForm.processing ? 'Mengunggah...' : 'Unggah Dokumen Rider' }}
                        </Button>
                    </CardContent>
                </Card>
            </CardContent>
        </Card>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <div id="tabs-top-anchor" class="scroll-mt-24"></div>
                <Tabs v-model="activeTab" defaultValue="profil">
                    <TabsList class="grid grid-cols-2 sm:grid-cols-5 w-full h-auto p-1.5 gap-1.5 mb-3">
                        <TabsTrigger value="profil" class="flex items-center gap-1.5 h-9 whitespace-nowrap" @click="activeTab = 'profil'"><User class="h-3.5 w-3.5" /> Profil Rider</TabsTrigger>
                        <TabsTrigger value="kelas" class="flex items-center gap-1.5 h-9 whitespace-nowrap" @click="activeTab = 'kelas'"><Layers class="h-3.5 w-3.5" /> Kelas & Item</TabsTrigger>
                        <TabsTrigger value="dokumen" class="flex items-center gap-1.5 h-9 whitespace-nowrap" @click="activeTab = 'dokumen'"><FileText class="h-3.5 w-3.5" /> Dokumen Rider</TabsTrigger>
                        <TabsTrigger value="pembayaran" class="flex items-center gap-1.5 h-9 whitespace-nowrap" @click="activeTab = 'pembayaran'"><Receipt class="h-3.5 w-3.5" /> Invoice & Bukti</TabsTrigger>
                        <TabsTrigger value="log" class="flex items-center gap-1.5 h-9 whitespace-nowrap" @click="activeTab = 'log'"><History class="h-3.5 w-3.5" /> Activity Log</TabsTrigger>
                    </TabsList>

                    <TabsContent value="profil">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <User class="h-5 w-5 text-orange-500" />
                                    Data Diri Rider
                                </CardTitle>
                                <CardDescription>Identitas lengkap peserta pendaftar</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 text-sm text-muted-foreground mb-0.5">
                                            <IdCard class="h-3.5 w-3.5" /> Nama Lengkap (KTP)
                                        </div>
                                        <p class="font-medium">{{ registration.rider?.full_name ?? registration.full_name ?? '-' }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="text-sm text-muted-foreground mb-0.5">Nama Jersey</div>
                                        <p class="font-medium">{{ registration.rider?.jersey_name ?? registration.jersey_name ?? '-' }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 text-sm text-muted-foreground mb-0.5">
                                            <IdCard class="h-3.5 w-3.5" /> NIK / Nomor Identitas
                                        </div>
                                        <p class="font-mono text-sm">{{ registration.rider?.id_number ?? registration.id_number ?? '-' }}</p>
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
                                        <p class="font-medium">{{ registration.rider?.phone ?? registration.phone ?? '-' }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 text-sm text-muted-foreground mb-0.5">
                                            <Mail class="h-3.5 w-3.5" /> Email
                                        </div>
                                        <p class="font-medium">{{ registration.rider?.email ?? registration.email ?? registration.rider?.user?.email ?? '-' }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="text-sm text-muted-foreground mb-0.5">Nama Tim</div>
                                        <p class="font-medium">{{ registration.team_name ?? registration.rider?.team_name ?? '-' }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="text-sm text-muted-foreground mb-0.5">No. Start Utama</div>
                                        <p class="font-mono font-semibold text-lg text-orange-600 dark:text-orange-500">{{ registration.start_number_main ?? '-' }}</p>
                                    </div>
                                    <div class="space-y-1 md:col-span-2">
                                        <div class="flex items-center gap-2 text-sm text-muted-foreground mb-0.5">
                                            <MapPin class="h-3.5 w-3.5" /> Domisili
                                        </div>
                                        <p class="font-medium">{{ registration.rider?.regency ?? registration.regency ?? '-' }}<template v-if="registration.rider?.province || registration.province">, {{ registration.rider?.province ?? registration.province }}</template></p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="kelas">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Layers class="h-5 w-5 text-orange-500" />
                                    Kelas Yang Diikuti ({{ registration.items?.length ?? 0 }})
                                </CardTitle>
                                <CardDescription>Multi-kelas dalam satu transaksi event</CardDescription>
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
                                                Tidak ada kelas yang dipilih pada transaksi ini.
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
                    </TabsContent>

                    <TabsContent value="dokumen">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <FileText class="h-5 w-5 text-orange-500" />
                                    Dokumen Rider (KIS / KTA IOF / Surat Rekomendasi)
                                </CardTitle>
                                <CardDescription>
                                    Semua dokumen yang diunggah peserta saat registrasi, atau diunggah manual oleh admin.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <template v-if="registration.documents && registration.documents.length > 0">
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div
                                            v-for="(doc, i) in registration.documents"
                                            :key="'doc-' + doc.id"
                                            class="rounded-lg border bg-white dark:bg-gray-900 p-4 space-y-2.5"
                                        >
                                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                                <Badge variant="outline" :class="documentBadgeColor(doc.type)">
                                                    {{ documentTypeLabel(doc.type) }}
                                                </Badge>
                                                <span class="text-[11px] text-muted-foreground">#{{ i + 1 }}</span>
                                            </div>
                                            <div class="space-y-0.5">
                                                <div class="text-sm font-semibold truncate">
                                                    {{ doc.file_name ?? (doc.file_path ? doc.file_path.split('/').pop() : 'Dokumen #' + (i + 1)) }}
                                                </div>
                                                <div class="text-xs text-muted-foreground space-y-0.5">
                                                    <div>Version: v{{ doc.version ?? 1 }}</div>
                                                    <div>Diunggah: {{ formatDateTime(doc.uploaded_at ?? doc.generated_at ?? doc.created_at) }}</div>
                                                    <div v-if="doc.uploaded_by || doc.uploader_name">
                                                        Pengunggah: {{ doc.uploader_name ?? 'Admin ID#' + doc.uploaded_by }}
                                                    </div>
                                                </div>
                                            </div>
                                            <Separator />
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <Link
                                                    v-if="doc.file_path"
                                                    :href="'/storage/' + doc.file_path"
                                                    target="_blank"
                                                    class="inline-flex"
                                                >
                                                    <Button variant="outline" size="sm" type="button" class="text-xs">
                                                        <FileText class="mr-1.5 h-3.5 w-3.5" /> Lihat / Download
                                                    </Button>
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="text-center py-10 text-sm text-muted-foreground italic border rounded-lg bg-muted/30">
                                        <FileText class="mx-auto mb-2 h-10 w-10 opacity-40" />
                                        <p>Belum ada dokumen rider yang diunggah untuk pendaftaran ini.</p>
                                    </div>
                                </template>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="pembayaran">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Receipt class="h-5 w-5 text-orange-500" />
                                    Invoice & Bukti Pembayaran
                                </CardTitle>
                                <CardDescription>Atur verifikasi bukti transfer (Approved / Rejected) sekaligus ubah status pendaftaran.</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div id="invoice-detail-row" class="rounded-lg border p-4 bg-white dark:bg-gray-900 transition-all">
                                    <div class="flex items-center justify-between gap-3 flex-wrap">
                                        <div>
                                            <div class="text-xs uppercase tracking-wide text-muted-foreground mb-0.5">Invoice</div>
                                            <div v-if="registration.invoice" class="font-bold text-lg">
                                                {{ registration.invoice.invoice_number }}
                                            </div>
                                            <div v-else class="text-sm text-muted-foreground italic">Belum ada invoice (generate manual di panel Checklist kiri).</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-xs uppercase tracking-wide text-muted-foreground mb-0.5">Status Lunas</div>
                                            <div class="font-bold text-lg text-orange-600 dark:text-orange-500">
                                                {{ registration.invoice?.paid_at ? 'LUNAS ✓' : 'BELUM' }}
                                            </div>
                                            <div class="text-xs text-muted-foreground">{{ formatDateTime(registration.invoice?.paid_at) }}</div>
                                        </div>
                                    </div>
                                    <div v-if="registration.invoice?.total !== undefined && registration.invoice.total !== null" class="mt-3 grid sm:grid-cols-2 gap-2 text-xs pt-2 border-t border-dashed">
                                        <div class="flex justify-between"><span class="text-muted-foreground">Total Invoice:</span> <span class="font-semibold">{{ formatCurrency(Number(registration.invoice.total)) }}</span></div>
                                        <div class="flex justify-between"><span class="text-muted-foreground">Email Invoice:</span> <span :class="registration.invoice.emailed_at ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-amber-600 font-medium'">{{ registration.invoice.emailed_at ? `Terkirim ${formatDateTime(registration.invoice.emailed_at)}` : 'Belum dikirim' }}</span></div>
                                    </div>
                                </div>

                                <Separator />

                                <div id="payment-proof-row" class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                            <Upload class="h-4 w-4 text-orange-500" />
                                            Daftar Bukti Transfer (Pilih Verifikasi)
                                        </div>
                                        <Badge variant="outline" class="text-[10.5px] border-gray-300 bg-gray-50">Total {{ registration.payment_proofs?.length ?? 0 }} file</Badge>
                                    </div>
                                </div>

                                <div v-if="registration.payment_proofs && registration.payment_proofs.length > 0" class="space-y-4">
                                    <div
                                        v-for="(proof, i) in registration.payment_proofs"
                                        :key="'pf-' + proof.id"
                                        :id="`proof-row-${proof.id}`"
                                        class="rounded-lg p-4 bg-white dark:bg-gray-900 transition-all"
                                        :class="[
                                            activeProofId === proof.id ? 'border-2 border-blue-500 shadow-md ring-2 ring-blue-100 dark:ring-blue-900/60 scale-[1.005]' : 'border',
                                        ]"
                                    >
                                        <div class="flex items-start justify-between gap-3 flex-wrap">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <p class="font-semibold text-sm">
                                                        Bukti #{{ i + 1 }}
                                                        <template v-if="proof.amount">
                                                            - {{ formatCurrency(Number(proof.amount)) }}
                                                        </template>
                                                    </p>
                                                    <Badge variant="outline" :class="statusBadgeClass(proof.verification_status ?? 'pending')">
                                                        {{ proof.verification_status === 'approved' ? 'APPROVED' : proof.verification_status === 'rejected' ? 'DITOLAK' : 'PENDING VERIFIKASI' }}
                                                    </Badge>
                                                    <Badge v-if="activeProofId === proof.id" class="border-blue-300 bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 text-[10px]">
                                                        ✔️ SEDANG DIPILIH
                                                    </Badge>
                                                </div>
                                                <p class="text-xs text-muted-foreground">
                                                    Nomor Invoice yang dimasukkan user: <span class="font-semibold">{{ proof.invoice_number ?? '-' }}</span>
                                                </p>
                                                <div class="text-xs text-muted-foreground flex items-center gap-2">
                                                    <span>Upload: {{ formatDateTime(proof.uploaded_at ?? proof.created_at) }}</span>
                                                    <span v-if="proof.verified_at">• Verif: {{ formatDateTime(proof.verified_at) }}</span>
                                                </div>
                                            </div>
                                            <div class="flex flex-col items-end gap-1">
                                                <div class="text-xs font-semibold">Pilih untuk verifikasi:</div>
                                                <div class="flex items-center gap-1.5">
                                                    <Select
                                                        :modelValue="activeProofId === proof.id ? activeVerificationStatus : ''"
                                                        @update:modelValue="(v) => { activeProofId = proof.id; activeVerificationStatus = v as any; }"
                                                    >
                                                        <SelectTrigger class="w-[220px] text-xs h-10 border-2">
                                                            <SelectValue placeholder="🔽 Klik pilih aksi verifikasi..." />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="pending" class="text-xs py-2">⏳ Tandai PENDING (Menunggu)</SelectItem>
                                                            <SelectItem value="approved" class="text-xs py-2 font-semibold text-emerald-700 dark:text-emerald-400">✅ SETUJUI (APPROVED) → BISA ENTRY LIST</SelectItem>
                                                            <SelectItem value="rejected" class="text-xs py-2 text-red-700 dark:text-red-400">❌ TOLAK (REJECTED)</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div v-if="activeProofId === proof.id" class="text-[11px] text-blue-700 dark:text-blue-300 font-medium bg-blue-50 dark:bg-blue-950/30 rounded px-2 py-1 mt-1">
                                                    💡 Verifikasi ini akan disimpan BERSAMAAN saat Anda klik SIMPAN STATUS di panel kanan (tidak perlu 2x save).
                                                </div>
                                            </div>
                                            <p v-if="proof.note" class="text-xs text-muted-foreground mt-2 italic">Catatan user: {{ proof.note }}</p>
                                            <p v-if="proof.rejection_reason" class="text-xs text-red-600 mt-1 font-medium">Alasan penolakan admin: {{ proof.rejection_reason }}</p>
                                            <div v-if="proof.file_path" class="mt-2">
                                                <Link
                                                    :href="'/storage/' + proof.file_path"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    <Button variant="outline" size="sm" type="button" class="text-xs">
                                                        <FileText class="mr-1.5 h-3.5 w-3.5" />
                                                        Lihat / Download Bukti Transfer
                                                        <span class="ml-1 text-[10px] text-muted-foreground">({{ proof.file_name ? proof.file_name : proof.file_path.split('/').pop() }})</span>
                                                    </Button>
                                                </Link>
                                            </div>
                                            <div
                                                v-if="activeProofId === proof.id && activeVerificationStatus === 'rejected'"
                                                class="mt-3 rounded-lg border border-red-200 bg-red-50 dark:bg-red-950/20 dark:border-red-900 p-3"
                                            >
                                            <Label class="text-xs font-semibold text-red-700 dark:text-red-400 mb-1 block">Alasan Penolakan (wajib jika ditolak):</Label>
                                            <Input v-model="activeRejectionReason" placeholder="Contoh: Bukti transfer buram, nominal tidak sesuai..." class="text-sm" />
                                        </div>
                                    </div>
                                </div>
                                </div>
                                <div v-else class="text-center py-8 text-sm text-muted-foreground border rounded-lg bg-muted/30">
                                    <Upload class="mx-auto mb-2 h-10 w-10 opacity-50" />
                                    <p>Belum ada bukti pembayaran yang diupload peserta.</p>
                                </div>

                                <Separator />

                                <div class="rounded-lg border border-blue-200 bg-blue-50/60 dark:bg-blue-950/20 dark:border-blue-900 p-3 text-xs text-blue-900 dark:text-blue-300">
                                    💡 <strong>Alur kerja satu klik:</strong> Pilih status verifikasi bukti (buktikan Approved) → pilih Status Baru menjadi "Approved" atau "Entry List" → klik SIMPAN di panel kanan.
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="log">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <History class="h-5 w-5 text-orange-500" />
                                    Activity Log Audit (Persetujuan & Perubahan Status)
                                </CardTitle>
                                <CardDescription>Semua perubahan status pendaftaran dicatat otomatis dengan timestamp & actor admin (fulfill requirement #5).</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ol v-if="registration.status_logs && registration.status_logs.length > 0" class="relative border-l border-gray-200 dark:border-gray-800 ml-2 space-y-5">
                                    <li
                                        v-for="log in registration.status_logs"
                                        :key="'log-' + log.id"
                                        class="ml-5"
                                    >
                                        <div class="absolute -left-[7px] mt-1.5 h-3.5 w-3.5 rounded-full bg-orange-500 ring-4 ring-orange-100 dark:ring-orange-950"></div>
                                        <div class="text-xs text-muted-foreground flex items-center gap-2">
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ log.changer?.name ?? (log.changed_by ? 'Admin ID#' + log.changed_by : 'Sistem') }}</span>
                                            <span>•</span>
                                            <span>{{ formatDateTime(log.created_at) }}</span>
                                        </div>
                                        <div class="mt-0.5 flex items-center gap-2 flex-wrap">
                                            <Badge variant="outline" class="text-[11px]" :class="statusColors[log.from_status ?? 'draft']">
                                                {{ statusLabelsMap[log.from_status ?? ''] ?? log.from_status ?? '-' }}
                                            </Badge>
                                            <ChevronLeft class="h-3 w-3 rotate-180 opacity-60" />
                                            <Badge class="text-[11px]" :class="statusColors[log.to_status ?? 'draft']">
                                                {{ statusLabelsMap[log.to_status ?? ''] ?? log.to_status ?? '-' }}
                                            </Badge>
                                        </div>
                                        <p v-if="log.note" class="mt-1.5 text-xs leading-relaxed text-gray-700 dark:text-gray-300 italic max-w-xl">{{ log.note }}</p>
                                    </li>
                                </ol>
                                <div v-else class="text-center py-10 text-sm text-muted-foreground italic">
                                    Belum ada perubahan status yang tercatat.
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
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
                        <div class="flex justify-between gap-2"><span class="text-muted-foreground">Waktu Daftar:</span> <span class="font-medium text-right">{{ formatDate(registration.created_at) }}</span></div>
                        <div class="flex justify-between gap-2 items-start pt-1"><span class="text-muted-foreground shrink-0">Status:</span> <Badge :class="statusColors[registration.status] || statusColors.draft" class="text-right">{{ registration.status_label }}</Badge></div>
                        <Separator class="my-2" />
                        <div class="flex justify-between gap-2"><span class="text-muted-foreground">Lunas:</span> <span class="font-medium text-right">{{ registration.is_paid ? '✅ Ya' : '⏳ Belum' }}</span></div>
                        <div class="flex justify-between gap-2"><span class="text-muted-foreground">Dokumen PDF:</span> <span class="font-medium text-right">{{ registration.can_generate_documents ? '✅ Bisa dibuat' : '🔒 Menunggu' }}</span></div>
                        <div class="flex justify-between gap-2"><span class="text-muted-foreground">Approved at:</span> <span class="font-medium text-right text-xs">{{ formatDateTime(registration.approved_at) }}</span></div>
                        <div class="flex justify-between gap-2"><span class="text-muted-foreground">Entry List Publik:</span> <span class="font-medium text-right text-xs" :class="registration.entry_list_published_at ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : ''">{{ registration.entry_list_published_at ? formatDateTime(registration.entry_list_published_at) : 'BELUM' }}</span></div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileSignature class="h-5 w-5 text-orange-500" />
                            Update Status & Approval
                        </CardTitle>
                        <CardDescription>Ubah status & catat perubahan ke audit log.</CardDescription>
                        <div class="mt-3 space-y-2.5">
                            <div class="flex items-center justify-between text-[11px] text-muted-foreground font-medium">
                                <span>Progress Kelengkapan Syarat Approve:</span>
                                <span :class="selectedApprovalScore === 3 ? 'text-emerald-600 dark:text-emerald-400 font-bold' : ''">{{ selectedApprovalScore }}/3 syarat</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                <div
                                    class="h-full transition-all duration-500 ease-out"
                                    :class="selectedApprovalScore === 3 ? 'bg-gradient-to-r from-emerald-500 to-green-500' : selectedApprovalScore === 2 ? 'bg-gradient-to-r from-amber-500 to-orange-500' : selectedApprovalScore === 1 ? 'bg-gradient-to-r from-blue-500 to-indigo-500' : 'bg-gray-400'"
                                    :style="{ width: `${(selectedApprovalScore / 3) * 100}%` }"
                                />
                            </div>
                            <div class="grid grid-cols-3 gap-1.5 text-[10px] font-semibold">
                                <div :class="(props.approval_checks.invoice_exists ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400') + ' rounded px-2 py-1 text-center'">
                                    {{ props.approval_checks.invoice_exists ? '✅' : '📋' }} Invoice
                                </div>
                                <div :class="(props.approval_checks.any_proof_submitted ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400') + ' rounded px-2 py-1 text-center'">
                                    {{ props.approval_checks.any_proof_submitted ? '✅' : '📤' }} Bukti Upload
                                </div>
                                <div :class="(props.approval_checks.proof_approved || (activeVerificationStatus === 'approved' && activeProofId) ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400') + ' rounded px-2 py-1 text-center'">
                                    {{ props.approval_checks.proof_approved || (activeVerificationStatus === 'approved' && activeProofId) ? '✅' : '✔️' }} Approved
                                </div>
                            </div>
                            <div v-if="selectedApprovalScore === 3" class="text-[11px] text-emerald-700 dark:text-emerald-400 font-semibold bg-emerald-50 dark:bg-emerald-950/30 rounded px-2.5 py-1.5 border border-emerald-200 dark:border-emerald-900">
                                ✅ Syarat SUDAH LENGKAP. Pilih status "Disetujui" di bawah → Klik SIMPAN → Otomatis status Entry List Publik!
                            </div>
                            <div v-else class="text-[11px] text-blue-700 dark:text-blue-400 font-medium bg-blue-50 dark:bg-blue-950/30 rounded px-2.5 py-1.5 border border-blue-200 dark:border-blue-900">
                                💡 Semua perubahan (verifikasi bukti + ubah status) disimpan SEKALI KLIK SIMPAN di bawah ini. Tidak perlu save berulang!
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="updateStatus" class="space-y-4">
                            <div v-if="!approval_checks.can_be_approved" class="rounded-lg border border-red-300 bg-red-50 dark:bg-red-950/20 dark:border-red-900 p-3.5 text-xs text-red-700 dark:text-red-300 space-y-2">
                                <div class="font-semibold text-sm flex items-center gap-1.5">
                                    <XCircle class="h-4 w-4" />
                                    ⚠️ GATING AKTIF: Approve / Entry List DITOLAK
                                </div>
                                <div class="text-[12px] leading-snug">
                                    Belum bisa Approve / Publish Entry List. Checklist berikut <strong>BELUM TERCAKUP SEMUA</strong>:
                                </div>
                                <ul class="list-disc list-outside ml-4 space-y-1 text-[12px]">
                                    <li :class="approval_checks.invoice_exists ? 'text-emerald-700 dark:text-emerald-400 line-through' : ''">
                                        <template v-if="approval_checks.invoice_exists">✅ SUDAH ADA:</template>
                                        <template v-else>❌ BELUM ADA:</template>
                                        Invoice harus dibuat (klik tombol <strong>✨ BUAT INVOICE SECARA MANUAL</strong> di panel kiri)
                                    </li>
                                    <li :class="approval_checks.any_proof_submitted ? 'text-emerald-700 dark:text-emerald-400 line-through' : ''">
                                        <template v-if="approval_checks.any_proof_submitted">✅ SUDAH DIUPLOAD:</template>
                                        <template v-else>❌ BELUM ADA UPLOAD:</template>
                                        Bukti transfer dari peserta
                                    </li>
                                    <li :class="approval_checks.proof_approved ? 'text-emerald-700 dark:text-emerald-400 line-through' : ''">
                                        <template v-if="approval_checks.proof_approved">✅ SUDAH DI-APPROVE:</template>
                                        <template v-else>❌ BELUM DIVERIFIKASI:</template>
                                        Bukti transfer status <strong>APPROVED</strong> (cek Tab Invoice & Bukti → pilih ✅ SETUJUI)
                                    </li>
                                </ul>
                                <div class="grid sm:grid-cols-2 gap-1.5 pt-1.5 border-t border-red-200/70 dark:border-red-800/50 mt-1">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="border-blue-400 text-blue-700 hover:bg-blue-50 dark:border-blue-700 dark:text-blue-300 dark:hover:bg-blue-950/30 text-xs"
                                        @click="!approval_checks.invoice_exists ? generateManualInvoice() : goToTab('pembayaran', 'invoice-detail-row', 'invoice-detail-row')"
                                    >
                                        <Sparkles v-if="!approval_checks.invoice_exists" class="h-3.5 w-3.5 mr-1.5" />
                                        <Receipt v-else class="h-3.5 w-3.5 mr-1.5" />
                                        {{ approval_checks.invoice_exists ? '🔽 BUKA TAB INVOICE' : '✨ BUAT INVOICE DULU' }}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="border-orange-400 text-orange-700 hover:bg-orange-50 dark:border-orange-700 dark:text-orange-300 dark:hover:bg-orange-950/30 text-xs"
                                        @click="quickApproveProofAndGoto"
                                    >
                                        <CheckCircle2 class="h-3.5 w-3.5 mr-1.5" />
                                        🔥 SETUJUI BUKTI & PINDAH TAB
                                    </Button>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="status" class="text-sm font-semibold">Status Baru (Tahapan Alur)</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger class="h-11">
                                        <SelectValue placeholder="Pilih status tujuan..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectLabel class="text-[11px] uppercase tracking-wider px-3 py-2 text-slate-500 dark:text-slate-400 font-bold">
                                                📋 Tahapan Draft / Pembayaran
                                            </SelectLabel>
                                            <SelectItem value="draft">
                                                <div class="flex items-center gap-2 py-0.5">
                                                    <Badge variant="outline" class="text-[10px] border-gray-300 text-gray-600 dark:border-gray-600 dark:text-gray-300">DRAFT</Badge>
                                                    <span>Draft (Kembalikan ke draft)</span>
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="pending_payment">
                                                <div class="flex items-center gap-2 py-0.5">
                                                    <Badge variant="outline" class="text-[10px] border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-300">WAIT</Badge>
                                                    <span>Menunggu Pembayaran (Pending Payment)</span>
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="payment_review">
                                                <div class="flex items-center gap-2 py-0.5">
                                                    <Badge variant="outline" class="text-[10px] border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-950/40 dark:text-blue-300">CEK</Badge>
                                                    <span>Pembayaran Dicek (Review Admin)</span>
                                                </div>
                                            </SelectItem>
                                        </SelectGroup>
                                        <SelectGroup>
                                            <SelectLabel class="text-[11px] uppercase tracking-wider px-3 py-2 text-slate-500 dark:text-slate-400 font-bold border-t pt-2.5 mt-1">
                                                ❌ Status Penolakan / Batal
                                            </SelectLabel>
                                            <SelectItem value="rejected">
                                                <div class="flex items-center gap-2 py-0.5">
                                                    <Badge variant="outline" class="text-[10px] border-red-300 bg-red-50 text-red-700 dark:border-red-700 dark:bg-red-950/40 dark:text-red-300">TOLAK</Badge>
                                                    <span>Ditolak (Pendaftaran ditolak / data tidak valid)</span>
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="cancelled">
                                                <div class="flex items-center gap-2 py-0.5">
                                                    <Badge variant="outline" class="text-[10px] border-slate-300 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">BATAL</Badge>
                                                    <span>Dibatalkan (Peserta mengundurkan diri)</span>
                                                </div>
                                            </SelectItem>
                                        </SelectGroup>
                                        <SelectGroup>
                                            <SelectLabel class="text-[11px] uppercase tracking-wider px-3 py-2 text-emerald-600 dark:text-emerald-400 font-bold border-t pt-2.5 mt-1">
                                                ✅ Tahapan Approval / Entry List (Publik)
                                            </SelectLabel>
                                            <SelectItem value="approved">
                                                <div class="flex items-center gap-2 py-0.5">
                                                    <Badge variant="outline" class="text-[10px] border-green-300 bg-green-50 text-green-700 dark:border-green-700 dark:bg-green-950/40 dark:text-green-300">APPROVE</Badge>
                                                    <span>Disetujui → <strong>otomatis promote ke Entry List</strong></span>
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="entry_list">
                                                <div class="flex items-center gap-2 py-0.5">
                                                    <Badge class="text-[10px] bg-emerald-500 text-white border-0">ENTRY</Badge>
                                                    <span>Entry List (Publish Publik / Peserta Final)</span>
                                                </div>
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <p class="text-[11px] text-muted-foreground -mt-1 leading-tight">
                                    💡 Flow Ringkas: Generate Invoice → Approve Bukti Transfer → Pilih status <strong>Disetujui</strong> → Status langsung menjadi <strong>Entry List (Publik)</strong>.
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="note">Catatan (opsional)</Label>
                                <Textarea id="note" v-model="form.note" rows="3" placeholder="Alasan perubahan status, nomor referensi verifikasi bukti transfer..." />
                            </div>
                            <Button
                                type="submit"
                                class="w-full bg-orange-500 hover:bg-orange-600 py-5 font-semibold"
                                :disabled="!canClickSubmit"
                            >
                                <FileSignature class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Menyimpan...' : 'SIMPAN PERUBAHAN STATUS' }}
                            </Button>
                            <p v-if="(form.status === 'approved' || form.status === 'entry_list') && !approval_checks.can_be_approved" class="text-[11px] text-red-600 dark:text-red-400 text-center">
                                Tombol di-disable karena syarat approval belum lengkap (lihat daftar checklist merah di atas).
                            </p>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>

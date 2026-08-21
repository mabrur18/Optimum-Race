<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Toaster } from '@/components/ui/sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Calendar, ChevronLeft, CreditCard, Layers, MapPin, ShieldAlert, Trophy, User, CheckCircle2, DollarSign, AlertTriangle, Ban, FileUp, Receipt } from 'lucide-vue-next';

interface RaceClassOpt {
    id: string;
    name: string;
    code: string;
    price: number;
    remaining_quota?: number | null;
}

interface EventReg {
    id: string;
    title: string;
    slug: string;
    location?: string | null;
    start_date: string;
    end_date?: string | null;
    eo_name?: string | null;
    discipline?: { name: string } | null;
    status?: string;
    available_classes?: RaceClassOpt[];
    eligibility_allowed_map?: Record<string, string[]>;
}

interface Props {
    event: EventReg;
    prefill_rider?: {
        full_name: string;
        jersey_name?: string | null;
        id_number: string;
        phone?: string | null;
        email?: string | null;
        regency: string;
        province: string;
    } | null;
}

const props = defineProps<Props>();
const page = usePage<any>();
const flash = page.props.flash as any;
const lastShownToastKey = ref<string>('');

watch(
    () => (page.props as any)?.flash?.toast as any,
    (toastData: any) => {
        if (!toastData || !toastData.type || !toastData.message) return;
        const key = `${toastData.type}:${toastData.message}`;
        if (lastShownToastKey.value === key) return;
        lastShownToastKey.value = key;
        (toast as any)[toastData.type](toastData.message);
    },
    { immediate: true }
);

const availableClasses = computed(() => props.event.available_classes ?? []);
const eligibilityMap = computed<Record<string, Set<string>>>(() => {
    const raw = props.event.eligibility_allowed_map ?? {};
    const out: Record<string, Set<string>> = {};
    Object.keys(raw).forEach(k => {
        out[k] = new Set(raw[k] ?? [k]);
    });
    return out;
});

const classesPriceMap = computed(() => {
    const map: Record<string, number> = {};
    availableClasses.value.forEach(c => { map[c.id] = c.price ?? 0; });
    return map;
});

const form = useForm({
    full_name: props.prefill_rider?.full_name ?? '',
    jersey_name: props.prefill_rider?.jersey_name ?? '',
    id_number: props.prefill_rider?.id_number ?? '',
    phone: props.prefill_rider?.phone ?? '',
    email: props.prefill_rider?.email ?? '',
    regency: props.prefill_rider?.regency ?? '',
    province: props.prefill_rider?.province ?? '',
    main_race_class_id: '' as string,
    additional_class_ids: [] as string[],
    team_name: '',
    start_number_main: '',
    agree_waiver: false as boolean,
    document_type: 'kis' as string,
    document_file: null as File | null,
    proof_file: null as File | null,
});

const documentFileInput = ref<HTMLInputElement | null>(null);
const openDocumentFilePicker = () => {
    if (documentFileInput.value) documentFileInput.value.click();
};

const onDocumentFileChange = (ev: Event) => {
    const target = ev.target as HTMLInputElement | null;
    if (!target || !target.files || target.files.length === 0) {
        form.document_file = null;
        return;
    }
    form.document_file = target.files[0];
};

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

const MAX_FILE_MB = 5;
const documentFileKb = computed(() => form.document_file ? Math.round((form.document_file.size ?? 0) / 1024) : 0);
const documentFileMb = computed(() => (documentFileKb.value / 1024).toFixed(2));
const documentFileValid = computed(() => {
    if (!form.document_file) return false;
    if ((form.document_file.size ?? 0) > MAX_FILE_MB * 1024 * 1024) return false;
    const ext = (form.document_file.name.split('.').pop() ?? '').toLowerCase();
    return ['pdf', 'jpg', 'jpeg', 'png'].includes(ext);
});
const documentFileName = computed(() => form.document_file?.name ?? 'Belum ada file dipilih');

const proofFileKb = computed(() => form.proof_file ? Math.round((form.proof_file.size ?? 0) / 1024) : 0);
const proofFileMb = computed(() => (proofFileKb.value / 1024).toFixed(2));
const proofExt = computed(() => {
    if (!form.proof_file?.name) return '';
    return (form.proof_file.name.split('.').pop() ?? '').toLowerCase();
});
const proofValid = computed(() => {
    if (!form.proof_file) return false;
    if ((form.proof_file.size ?? 0) > MAX_FILE_MB * 1024 * 1024) return false;
    return ['pdf', 'jpg', 'jpeg', 'png'].includes(proofExt.value);
});
const proofName = computed(() => form.proof_file?.name ?? 'Belum ada file dipilih');

const selectedRaceClassIds = computed<string[]>(() => {
    const out: string[] = [];
    const main = (form as any).main_race_class_id as string | undefined;
    if (typeof main === 'string' && main !== '') out.push(main);
    const adds = (form as any).additional_class_ids as string[] | undefined | null;
    if (Array.isArray(adds)) {
        adds.forEach(id => {
            if (typeof id === 'string' && id !== '' && id !== main && !out.includes(id)) out.push(id);
        });
    }
    return out;
});

const additionalEligibleClasses = computed<RaceClassOpt[]>(() => {
    if (!form.main_race_class_id) return [];
    const allowed = eligibilityMap.value[form.main_race_class_id];
    if (!allowed) return [];
    return availableClasses.value.filter(c =>
        c.id !== form.main_race_class_id && allowed.has(c.id)
    );
});

const selectedTotal = computed(() => {
    return selectedRaceClassIds.value.reduce((sum, id) => sum + (classesPriceMap.value[id] ?? 0), 0);
});

const toggleAdditionalClass = (id: string) => {
    if (!additionalEligibleClasses.value.some(c => c.id === id)) return;
    const arr = [...(form.additional_class_ids || [])];
    const idx = arr.indexOf(id);
    if (idx >= 0) {
        arr.splice(idx, 1);
    } else {
        arr.push(id);
    }
    form.additional_class_ids = arr;
};

const formatDate = (d?: string | null) => {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const formatCurrency = (a: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(a);
};

const getClassName = (id: string) => availableClasses.value.find(c => c.id === id)?.name ?? id;

const submitDisabled = computed(() =>
    form.processing ||
    !form.agree_waiver ||
    !form.main_race_class_id ||
    !documentFileValid.value ||
    !proofValid.value
);

const submit = () => {
    form.post(route('events.register.submit', props.event.slug), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            toast.success('Pendaftaran & bukti pembayaran berhasil dikirim! Silakan tunggu verifikasi admin maksimal 1x24 jam.', { duration: 4500 });
        },
        onError: (errors: any) => {
            const firstMsg = Object.values(errors)[0];
            toast.error(typeof firstMsg === 'string' ? firstMsg : 'Terjadi kesalahan saat mengirim pendaftaran. Silakan periksa isian Anda.', { duration: 4000 });
        },
    });
};
</script>

<template>
    <Toaster />
    <Head :title="'Daftar - ' + event.title" />

    <div class="min-h-screen bg-gradient-to-br from-orange-50 via-white to-orange-50 dark:from-gray-900 dark:via-gray-950 dark:to-gray-900">
        <nav class="border-b bg-white/80 backdrop-blur-sm dark:bg-gray-950/80 sticky top-0 z-50">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link :href="route('events.show', event.slug)" class="flex items-center gap-2">
                    <ChevronLeft class="h-4 w-4 text-muted-foreground" />
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-500 text-white">
                        <Trophy class="h-4 w-4" />
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white">Kembali ke Detail Event</span>
                </Link>
                <Badge variant="outline" class="gap-1.5 border-orange-300 text-orange-700 dark:border-orange-700 dark:text-orange-400">
                    <Layers class="h-3.5 w-3.5" /> {{ availableClasses.length }} Kelas Tersedia
                </Badge>
            </div>
        </nav>

        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <div v-if="flash?.success" class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
                {{ flash.success }}
            </div>
            <div v-if="flash?.error" class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                {{ flash.error }}
            </div>

            <div class="rounded-2xl bg-gradient-to-br from-orange-500 to-red-600 p-6 text-white mb-8 shadow-lg">
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight mb-2">
                    Form Pendaftaran {{ event.title }}
                </h1>
                <div class="flex flex-wrap items-center gap-3 text-sm text-white/90">
                    <span class="flex items-center gap-1.5"><Calendar class="h-4 w-4" />{{ formatDate(event.start_date) }}<template v-if="event.end_date && event.end_date !== event.start_date"> - {{ formatDate(event.end_date) }}</template></span>
                    <span v-if="event.location" class="flex items-center gap-1.5"><MapPin class="h-4 w-4" />{{ event.location }}</span>
                    <span class="flex items-center gap-1.5"><User class="h-4 w-4" />EO: {{ event.eo_name ?? '-' }}</span>
                </div>
            </div>

            <form @submit.prevent="submit" class="grid gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <User class="h-5 w-5 text-orange-500" />
                                Data Diri Rider
                            </CardTitle>
                            <CardDescription>Pastikan data sesuai dengan KTP / identitas asli untuk keperluan scrutineering</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2 md:col-span-2">
                                    <Label for="full_name">Nama Lengkap (Sesuai KTP) <span class="text-red-500">*</span></Label>
                                    <Input id="full_name" v-model="form.full_name" placeholder="Nama lengkap sesuai KTP" :error="form.errors.full_name" />
                                    <p v-if="form.errors.full_name" class="text-sm text-red-500">{{ form.errors.full_name }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="jersey_name">Nama Jersey (di motor)</Label>
                                    <Input id="jersey_name" v-model="form.jersey_name" placeholder="Contoh: BIMA 12" :error="form.errors.jersey_name" />
                                </div>
                                <div class="space-y-2">
                                    <Label for="id_number">NIK / Nomor Identitas <span class="text-red-500">*</span></Label>
                                    <Input id="id_number" v-model="form.id_number" placeholder="16 digit NIK KTP" :error="form.errors.id_number" />
                                    <p v-if="form.errors.id_number" class="text-sm text-red-500">{{ form.errors.id_number }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="phone">No. WhatsApp / Telepon</Label>
                                    <Input id="phone" v-model="form.phone" placeholder="08xx..." :error="form.errors.phone" />
                                </div>
                                <div class="space-y-2">
                                    <Label for="email">Email</Label>
                                    <Input id="email" type="email" v-model="form.email" placeholder="email@contoh.com" :error="form.errors.email" />
                                </div>
                                <div class="space-y-2">
                                    <Label for="regency">Kabupaten / Kota <span class="text-red-500">*</span></Label>
                                    <Input id="regency" v-model="form.regency" placeholder="Contoh: Kab. Banyumas" :error="form.errors.regency" />
                                    <p v-if="form.errors.regency" class="text-sm text-red-500">{{ form.errors.regency }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="province">Provinsi <span class="text-red-500">*</span></Label>
                                    <Input id="province" v-model="form.province" placeholder="Contoh: Jawa Tengah" :error="form.errors.province" />
                                    <p v-if="form.errors.province" class="text-sm text-red-500">{{ form.errors.province }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Layers class="h-5 w-5 text-orange-500" />
                                Pilihan Kelas Race <span class="text-red-500">*</span>
                                <Badge variant="outline" class="ml-auto">{{ selectedRaceClassIds.length }} kelas dipilih</Badge>
                            </CardTitle>
                            <CardDescription>
                                Pilih <strong>Kelas Utama</strong> (1 kelas) terlebih dahulu, lalu <strong>Kelas Tambahan</strong> akan otomatis menampilkan HANYA kelas yang eligible (dibolehkan) berdasarkan matriks panitia.
                                <span class="block text-orange-600 dark:text-orange-400 mt-1 font-medium">
                                    <AlertTriangle class="inline h-3.5 w-3.5 mr-1 -mt-0.5" />
                                    Kelas tambahan yang tidak sesuai matriks = TIDAK DITAMPILKAN.
                                </span>
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div class="space-y-3">
                                <Label for="main_race_class_id" class="text-sm font-semibold flex items-center gap-1.5">
                                    <Trophy class="h-4 w-4 text-orange-500" />
                                    Kelas Utama <span class="text-red-500">*</span>
                                </Label>
                                <Select v-model="form.main_race_class_id" required>
                                    <SelectTrigger id="main_race_class_id" :class="['h-12 text-base', form.errors.main_race_class_id ? 'border-red-500' : '']">
                                        <SelectValue placeholder="Pilih 1 kelas utama yang akan Anda ikuti (nomor start terpasang disini)" />
                                    </SelectTrigger>
                                    <SelectContent class="max-h-[320px]">
                                        <SelectGroup>
                                            <SelectLabel>Kelas Race Tersedia</SelectLabel>
                                            <SelectItem
                                                v-for="cls in availableClasses"
                                                :key="cls.id"
                                                :value="cls.id"
                                            >
                                                <div class="flex items-center justify-between gap-4 pr-2">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <Trophy class="h-4 w-4 text-orange-500 shrink-0" />
                                                        <span class="font-medium truncate">{{ cls.name }}</span>
                                                        <code class="rounded bg-muted px-1.5 py-0.5 text-[10px] shrink-0">{{ cls.code }}</code>
                                                    </div>
                                                    <div class="flex items-center gap-3 shrink-0">
                                                        <Badge v-if="cls.remaining_quota !== undefined && cls.remaining_quota !== null && cls.remaining_quota <= 0" variant="destructive" class="text-[10px] py-0 px-2 gap-1">
                                                            PENUH
                                                        </Badge>
                                                        <Badge v-else class="bg-green-100 text-green-700 border-0 text-[10px] py-0 px-2 gap-1 dark:bg-green-900/40 dark:text-green-300">
                                                            {{ cls.remaining_quota ?? '∞' }} slot
                                                        </Badge>
                                                        <span class="font-bold text-orange-600 dark:text-orange-500">
                                                            {{ formatCurrency(cls.price) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.main_race_class_id" class="text-sm text-red-500">{{ form.errors.main_race_class_id }}</p>
                            </div>

                            <Separator />

                            <div class="space-y-3">
                                <Label class="text-sm font-semibold flex items-center gap-1.5">
                                    <CheckCircle2 class="h-4 w-4 text-green-600 dark:text-green-400" />
                                    Kelas Tambahan (Elible Berdasarkan Matriks)
                                    <Badge v-if="additionalEligibleClasses.length === 0 && form.main_race_class_id" variant="outline" class="ml-auto text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-700">
                                        TIDAK ADA OPSI TAMBAHAN
                                    </Badge>
                                    <Badge v-else-if="additionalEligibleClasses.length > 0" variant="outline" class="ml-auto">
                                        {{ additionalEligibleClasses.length }} kelas eligible
                                    </Badge>
                                </Label>
                                <p class="text-xs text-muted-foreground -mt-1.5">
                                    Hanya kelas yang <strong>BOLEH digabung</strong> dengan kelas utama (berdasarkan matriks panitia) yang tampil. Kelas lain otomatis disembunyikan untuk menghindari kesalahan input!
                                </p>

                                <template v-if="!form.main_race_class_id">
                                    <div class="rounded-xl border-2 border-dashed p-6 text-center text-muted-foreground italic bg-muted/20">
                                        <Trophy class="mx-auto mb-2 h-10 w-10 opacity-40" />
                                        Silakan pilih <strong>Kelas Utama</strong> terlebih dahulu untuk melihat kelas tambahan yang boleh Anda ikuti.
                                    </div>
                                </template>
                                <template v-else-if="additionalEligibleClasses.length === 0">
                                    <div class="rounded-xl border-2 border-amber-200 bg-amber-50/60 p-6 text-center dark:border-amber-800 dark:bg-amber-950/20">
                                        <Ban class="mx-auto mb-2 h-9 w-9 text-amber-500 opacity-70" />
                                        <p class="font-semibold text-amber-800 dark:text-amber-300">Tidak ada kelas tambahan yang eligible untuk kelas utama ini.</p>
                                        <p class="text-sm text-amber-700/80 dark:text-amber-400/70 mt-1">Anda hanya akan mengikuti 1 kelas (kelas utama saja).</p>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="space-y-3">
                                        <div
                                            v-for="cls in additionalEligibleClasses"
                                            :key="cls.id"
                                            :class="[
                                                'rounded-xl border-2 p-4 transition-all cursor-pointer',
                                                form.additional_class_ids.includes(cls.id)
                                                    ? 'border-orange-500 bg-orange-50 dark:bg-orange-950/20 shadow-sm'
                                                    : 'border-border bg-card hover:border-green-400 hover:bg-green-50/30 dark:hover:border-green-800'
                                            ]"
                                            @click="toggleAdditionalClass(cls.id)"
                                        >
                                            <div class="flex items-start gap-3">
                                                <Checkbox
                                                    :checked="form.additional_class_ids.includes(cls.id)"
                                                    class="mt-0.5 data-[state=checked]:bg-green-600 data-[state=checked]:border-green-600"
                                                />
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                                        <span class="font-bold text-base">{{ cls.name }}</span>
                                                        <code class="rounded bg-muted px-1.5 py-0.5 text-[11px]">{{ cls.code }}</code>
                                                        <Badge class="text-[10px] gap-1 bg-green-100 text-green-800 hover:bg-green-100 border-0 dark:bg-green-900/40 dark:text-green-300">
                                                            <CheckCircle2 class="h-2.5 w-2.5" />
                                                            ELIGIBLE
                                                        </Badge>
                                                        <Badge v-if="cls.remaining_quota !== undefined && cls.remaining_quota !== null && cls.remaining_quota <= 0" variant="destructive" class="text-[11px] gap-1">
                                                            <span>PENUH</span>
                                                        </Badge>
                                                        <Badge v-else class="bg-slate-100 text-slate-700 hover:bg-slate-100 border-0 text-[11px] gap-1 dark:bg-slate-800 dark:text-slate-200">
                                                            {{ cls.remaining_quota ?? '∞' }} slot
                                                        </Badge>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4 flex-wrap">
                                                        <p class="text-xs text-muted-foreground">
                                                            ✅ Dapat digabung dengan kelas utama
                                                        </p>
                                                        <span class="font-black text-lg text-orange-600 dark:text-orange-500 shrink-0">
                                                            {{ formatCurrency(cls.price) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <p v-if="form.errors.race_class_ids" class="text-sm text-red-500 mt-3">{{ form.errors.race_class_ids }}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Layers class="h-5 w-5 text-orange-500" />
                                Upload Dokumen Pendukung
                                <span class="text-red-500 ml-1">*</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label>Jenis Dokumen</Label>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <label
                                        class="cursor-pointer rounded-xl border p-4 transition-all has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 has-[:checked]:ring-2 has-[:checked]:ring-orange-200 dark:has-[:checked]:bg-orange-950/20 dark:has-[:checked]:ring-orange-900/60"
                                    >
                                        <input
                                            type="radio"
                                            class="sr-only"
                                            name="document_type"
                                            value="kis"
                                            v-model="form.document_type"
                                        />
                                        <div class="flex flex-col gap-1">
                                            <span class="font-semibold text-sm">KIS</span>
                                            <span class="text-xs text-muted-foreground">Kartu Identitas Sepeda</span>
                                        </div>
                                    </label>
                                    <label
                                        class="cursor-pointer rounded-xl border p-4 transition-all has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 has-[:checked]:ring-2 has-[:checked]:ring-orange-200 dark:has-[:checked]:bg-orange-950/20 dark:has-[:checked]:ring-orange-900/60"
                                    >
                                        <input
                                            type="radio"
                                            class="sr-only"
                                            name="document_type_2"
                                            value="kta_iof"
                                            v-model="form.document_type"
                                        />
                                        <div class="flex flex-col gap-1">
                                            <span class="font-semibold text-sm">KTA IOF</span>
                                            <span class="text-xs text-muted-foreground">Kartu Tanda Anggota IOF</span>
                                        </div>
                                    </label>
                                    <label
                                        class="cursor-pointer rounded-xl border p-4 transition-all has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 has-[:checked]:ring-2 has-[:checked]:ring-orange-200 dark:has-[:checked]:bg-orange-950/20 dark:has-[:checked]:ring-orange-900/60"
                                    >
                                        <input
                                            type="radio"
                                            class="sr-only"
                                            name="document_type_3"
                                            value="surat_rekomendasi"
                                            v-model="form.document_type"
                                        />
                                        <div class="flex flex-col gap-1">
                                            <span class="font-semibold text-sm">Surat Rekomendasi</span>
                                            <span class="text-xs text-muted-foreground">Rekomendasi klub / official</span>
                                        </div>
                                    </label>
                                </div>
                                <p v-if="form.errors.document_type" class="text-sm text-red-500 mt-1">{{ form.errors.document_type }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="document_file">Unggah File Dokumen</Label>
                                <div
                                    class="rounded-xl border-2 border-dashed p-5 transition-colors hover:border-orange-400"
                                    :class="documentFileValid ? 'border-green-400 bg-green-50/40 dark:bg-green-950/20' : (form.document_file ? 'border-red-400 bg-red-50/40 dark:bg-red-950/20' : 'border-gray-300 dark:border-gray-700')"
                                >
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-orange-100 dark:bg-orange-950/50 text-orange-600 dark:text-orange-400">
                                            <Layers class="h-7 w-7" />
                                        </div>
                                        <div class="flex-1 min-w-0 space-y-1.5">
                                            <div class="text-sm font-medium truncate">{{ documentFileName }}</div>
                                            <div class="text-xs text-muted-foreground flex items-center gap-2">
                                                <span v-if="form.document_file">Ukuran: <strong>{{ documentFileMb }} MB</strong></span>
                                                <span v-else>Tidak ada file dipilih</span>
                                                <span class="opacity-60">• Maks {{ MAX_FILE_MB }} MB</span>
                                            </div>
                                            <div class="text-xs font-medium" :class="documentFileValid ? 'text-green-600 dark:text-green-400' : 'text-red-500'">
                                                <template v-if="!form.document_file">Menunggu file dipilih...</template>
                                                <template v-else-if="documentFileValid">✅ Format file valid (PDF / JPG / PNG)</template>
                                                <template v-else>❌ Format atau ukuran file tidak sesuai. Hanya PDF, JPG, JPEG, PNG maksimal 5MB.</template>
                                            </div>
                                        </div>
                                        <div class="shrink-0">
                                            <input
                                                ref="documentFileInput"
                                                id="document_file"
                                                type="file"
                                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                                class="sr-only"
                                                @change="onDocumentFileChange"
                                            />
                                            <Button type="button" variant="outline" size="sm" @click="openDocumentFilePicker">Pilih File</Button>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-muted-foreground leading-relaxed">
                                    <span class="font-semibold text-orange-600 dark:text-orange-400">Deskripsi:</span>
                                    File yang diunggah harus berupa KIS, KTA IOF, atau Surat rekomendasi sesuai ketentuan.
                                </p>
                                <p v-if="form.errors.document_file" class="text-sm text-red-500">{{ form.errors.document_file }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <CreditCard class="h-5 w-5 text-orange-500" />
                                Tambahan (Opsional)
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="team_name">Nama Tim</Label>
                                    <Input id="team_name" v-model="form.team_name" placeholder="Contoh: BKS Racing Team" :error="form.errors.team_name" />
                                </div>
                                <div class="space-y-2">
                                    <Label for="start_number_main">Nomor Start (Kelas Utama)</Label>
                                    <Input id="start_number_main" v-model="form.start_number_main" placeholder="Contoh: 12" :error="form.errors.start_number_main" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-orange-300 dark:border-orange-700">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <ShieldAlert class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                Pernyataan & Kesanggupan
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <p class="text-sm leading-relaxed text-muted-foreground">
                                Dengan ini saya menyatakan bahwa data yang saya isikan adalah BENAR dan saya bersedia mengikuti seluruh rangkaian acara, menjunjung tinggi sportifitas, mentaati peraturan race, serta menerima seluruh resiko yang mungkin terjadi selama mengikuti event. Saya bersedia dicek (scrutineering) dan mematuhi keputusan panitia yang tidak dapat diganggu gugat.
                            </p>
                            <div class="flex items-start gap-3 rounded-lg bg-orange-50 dark:bg-orange-950/20 p-4">
                                <Checkbox
                                    id="agree_waiver"
                                    v-model="form.agree_waiver"
                                    class="mt-0.5 data-[state=checked]:bg-orange-500 data-[state=checked]:border-orange-500"
                                />
                                <Label for="agree_waiver" class="text-sm font-semibold leading-tight cursor-pointer select-none">
                                    <span class="text-red-500">*</span> Saya SETUJU dan MENYETUJUI seluruh pernyataan di atas serta syarat & ketentuan berlaku
                                </Label>
                            </div>
                            <p v-if="form.errors.agree_waiver" class="text-sm text-red-500">{{ form.errors.agree_waiver }}</p>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-6">
                    <Card class="sticky top-24 border-orange-400 dark:border-orange-700 shadow-xl">
                        <CardHeader class="pb-3 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-950/40 dark:to-amber-950/30 border-b border-orange-200 dark:border-orange-900">
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <Receipt class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                Ringkasan Biaya & Bukti Pembayaran
                                <Badge class="ml-auto bg-red-500 text-white border-0">WAJIB</Badge>
                            </CardTitle>
                            <CardDescription class="text-xs">
                                Upload bukti transfer langsung di halaman ini agar tidak bolak-balik! Admin cukup 1x verifikasi → status Entry List.
                            </CardDescription>
                        </CardHeader>
                        <Separator />
                        <CardContent class="pt-5 space-y-5">
                            <template v-if="selectedRaceClassIds.length > 0">
                                <div v-for="cid in selectedRaceClassIds" :key="cid" class="flex justify-between items-start gap-3 text-sm">
                                    <div class="flex items-start gap-2 min-w-0">
                                        <Badge v-if="cid === form.main_race_class_id" class="bg-orange-500 text-white border-0 shrink-0 mt-0.5 h-5">
                                            UTAMA
                                        </Badge>
                                        <Badge v-else variant="outline" class="shrink-0 mt-0.5 h-5">
                                            TAMBAHAN
                                        </Badge>
                                        <span class="text-muted-foreground truncate">
                                            {{ getClassName(cid) }}
                                        </span>
                                    </div>
                                    <span class="font-medium shrink-0">
                                        {{ formatCurrency(classesPriceMap[cid] ?? 0) }}
                                    </span>
                                </div>
                                <Separator />
                                <div class="flex justify-between items-center pt-1">
                                    <span class="text-base font-bold">TOTAL BAYAR</span>
                                    <span class="text-2xl font-black text-orange-600 dark:text-orange-500">
                                        {{ formatCurrency(selectedTotal) }}
                                    </span>
                                </div>
                                <div class="rounded-lg border-2 border-dashed border-amber-300 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30 p-3 space-y-1.5">
                                    <div class="text-xs font-semibold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                                        <CreditCard class="h-3.5 w-3.5" />
                                        Transfer ke rekening berikut:
                                    </div>
                                    <div class="text-xs text-amber-900/90 dark:text-amber-200 space-y-0.5">
                                        <div><strong>Bank BRI</strong> · 0987-01-001234-56-7</div>
                                        <div>a.n. <strong>PT Optimum Racing System</strong></div>
                                        <div class="font-bold text-orange-700 dark:text-orange-400">Total: {{ formatCurrency(selectedTotal) }}</div>
                                    </div>
                                </div>
                                <Separator />
                                <div class="space-y-2">
                                    <Label for="proof_file" class="text-sm font-semibold flex items-center gap-1.5">
                                        <FileUp class="h-4 w-4 text-red-500" />
                                        Upload Bukti Transfer
                                        <span class="text-red-500 ml-1">*</span>
                                    </Label>
                                    <div
                                        class="rounded-xl border-2 border-dashed p-4 transition-colors hover:border-orange-400"
                                        :class="proofValid ? 'border-green-400 bg-green-50/40 dark:bg-green-950/20' : (form.proof_file ? 'border-red-400 bg-red-50/40 dark:bg-red-950/20' : 'border-gray-300 dark:border-gray-700')"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-orange-100 dark:bg-orange-950/50 text-orange-600 dark:text-orange-400">
                                                <FileUp class="h-6 w-6" />
                                            </div>
                                            <div class="flex-1 min-w-0 space-y-1">
                                                <div class="text-sm font-medium truncate">{{ proofName }}</div>
                                                <div class="text-[11px] text-muted-foreground flex items-center gap-2">
                                                    <span v-if="form.proof_file">Ukuran: <strong>{{ proofFileMb }} MB</strong></span>
                                                    <span v-else>Tidak ada file dipilih</span>
                                                    <span class="opacity-60">• Max {{ MAX_FILE_MB }}MB</span>
                                                    <span v-if="form.proof_file" class="opacity-60">• {{ proofExt.toUpperCase() }}</span>
                                                </div>
                                                <div class="text-[11px] font-medium" :class="proofValid ? 'text-green-600 dark:text-green-400' : 'text-red-500'">
                                                    <template v-if="!form.proof_file">Wajib upload bukti transfer...</template>
                                                    <template v-else-if="proofValid">✅ File valid (PDF/JPG/PNG)</template>
                                                    <template v-else>❌ Format salah / > {{ MAX_FILE_MB }}MB</template>
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
                                                <Button type="button" variant="outline" size="sm" @click="openProofFilePicker">Pilih</Button>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.proof_file" class="text-sm text-red-500">{{ form.errors.proof_file }}</p>
                                </div>
                            </template>
                            <template v-else>
                                <div class="text-center py-6 text-muted-foreground italic">
                                    <Layers class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                    Pilih <strong>Kelas Utama</strong> untuk melihat rincian biaya pendaftaran
                                </div>
                            </template>
                        </CardContent>
                        <CardFooter class="flex-col gap-3 pt-0 border-t mt-3">
                            <Button
                                type="submit"
                                class="w-full bg-gradient-to-br from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 font-semibold py-6 text-base shadow-lg"
                                :disabled="submitDisabled"
                            >
                                <CheckCircle2 class="mr-2 h-5 w-5" />
                                {{ form.processing ? 'Menyimpan Pendaftaran...' : 'KIRIM PENDAFTARAN + BUKTI PEMBAYARAN' }}
                            </Button>
                            <p class="text-[11px] text-muted-foreground text-center leading-relaxed">
                                ✅ Sekarang <strong>TIDAK PERLU</strong> buka halaman lain lagi. Cukup 1x submit, admin cek bukti → status otomatis Entry List jika lolos.
                            </p>
                            <Separator />
                            <Link :href="route('events.show', event.slug)" class="w-full">
                                <Button variant="outline" type="button" class="w-full">Batalkan</Button>
                            </Link>
                        </CardFooter>
                    </Card>
                </div>
            </form>
        </div>
    </div>
</template>

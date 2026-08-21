<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Toaster } from '@/components/ui/sonner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { login, register } from '@/routes';
import { Calendar, ChevronLeft, Layers, MapPin, Trophy, Users, DollarSign, CheckCircle2, XCircle, Clock, ShieldCheck, Flag } from 'lucide-vue-next';
import type { FlashToast } from '@/types/ui';

interface RaceClassItem {
    id: string;
    name: string;
    code: string;
    level_order?: number;
    quota: number | null;
    price: number;
    price_formatted: string;
    remaining_quota?: number | null;
    is_full?: boolean;
}

interface EntryListRow {
    registration_id: string;
    full_name: string | null;
    team_name: string | null;
    start_number: string | null;
}

interface EventDetail {
    id: string;
    title: string;
    slug: string;
    location: string | null;
    start_date: string;
    end_date: string | null;
    eo_name: string | null;
    status: string;
    description?: string | null;
    total_registrations?: number;
    is_open_for_registration?: boolean;
    has_existing_registration?: boolean;
    discipline?: { name: string; slug?: string } | null;
    organizer?: { id: string; name: string; slug?: string; logo_url?: string | null } | null;
    race_classes: RaceClassItem[];
}

interface Props {
    event: EventDetail;
    has_existing_registration: boolean;
    entry_list_by_race_class?: Record<string, EntryListRow[]>;
}

const props = defineProps<Props>();
const page = usePage<any>();
const user = (page.props.auth as any)?.user;
const flash = page.props.flash as any;
const lastShownToastKey = ref<string>('');

watch(
    () => (page.props as any)?.flash?.toast as FlashToast | undefined | null,
    (toastData) => {
        if (!toastData || !toastData.type || !toastData.message) return;
        const key = `${toastData.type}:${toastData.message}`;
        if (lastShownToastKey.value === key) return;
        lastShownToastKey.value = key;
        toast[toastData.type](toastData.message);
    },
    { immediate: true }
);

const formatDate = (d: string) => {
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const formatCurrency = (v: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(v ?? 0);
};

const statusLabels: Record<string, string> = {
    draft: 'Persiapan',
    published: 'Pendaftaran Dibuka',
    closed: 'Pendaftaran Ditutup',
    archived: 'Diarsipkan',
};
const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    published: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
    closed: 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
    archived: 'bg-slate-100 text-slate-800',
};

const entryListMap = computed<Record<string, EntryListRow[]>>(() => (props.entry_list_by_race_class as Record<string, EntryListRow[]>) ?? {});
const totalEntryListRiders = computed(() => {
    let n = 0;
    Object.values(entryListMap.value).forEach((rows) => (n += rows.length));
    return n;
});
const entryListRaceClassIds = computed(() => {
    return (props.event.race_classes ?? []).map((c) => c.id);
});

const getInitialEntryListTab = (): string => {
    const classes = props.event.race_classes ?? [];
    const hasClass = classes.find((c) => (entryListMap.value[c.id]?.length ?? 0) > 0);
    return hasClass?.id ?? (entryListRaceClassIds.value[0] ?? '');
};
const entryListActiveTab = ref<string>(getInitialEntryListTab());
</script>

<template>
    <Toaster />
    <Head :title="event.title + ' - Optimum Race'">
        <meta name="description" :content="`${event.eo_name ?? 'Penyelenggara'} menyelenggarakan ${event.title} di ${event.location ?? 'lokasi akan diumumkan'}. Daftarkan diri Anda sekarang!`" />
    </Head>

    <div class="min-h-screen bg-gradient-to-br from-orange-50 via-white to-orange-50 dark:from-gray-900 dark:via-gray-950 dark:to-gray-900">
        <nav class="border-b bg-white/80 backdrop-blur-sm dark:bg-gray-950/80 sticky top-0 z-50">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link :href="route('home')" class="flex items-center gap-2">
                    <ChevronLeft class="h-4 w-4 text-muted-foreground" />
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-500 text-white">
                        <Trophy class="h-4 w-4" />
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white">
                        Optimum<span class="text-orange-500">Race</span>
                    </span>
                </Link>

                <div class="flex items-center gap-3">
                    <template v-if="user">
                        <Link :href="route('dashboard')">
                            <Button variant="ghost" size="sm">Dashboard</Button>
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="login()">
                            <Button variant="ghost" size="sm">Log in</Button>
                        </Link>
                        <Link :href="register()">
                            <Button variant="default" size="sm" class="bg-orange-500 hover:bg-orange-600">Register</Button>
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div v-if="flash?.success" class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
                {{ flash.success }}
            </div>
            <div v-if="flash?.error" class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                {{ flash.error }}
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 to-red-600 p-8 text-white shadow-xl sm:p-12">
                <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-black/10 blur-3xl"></div>

                <div class="relative">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="space-y-4 max-w-3xl">
                            <div class="flex items-center gap-2 flex-wrap">
                                <Badge class="bg-white/20 text-white backdrop-blur-sm hover:bg-white/20 border-0">
                                    {{ event.discipline?.name ?? 'Event Otomotif' }}
                                </Badge>
                                <Badge :class="['border-0', statusColors[event.status] || 'bg-gray-700 text-white']">
                                    {{ statusLabels[event.status] ?? event.status }}
                                </Badge>
                                <Badge v-if="event.has_existing_registration" class="bg-emerald-400/30 text-emerald-50 border-0">
                                    <CheckCircle2 class="mr-1 h-3.5 w-3.5" /> Anda Sudah Terdaftar
                                </Badge>
                            </div>
                            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">{{ event.title }}</h1>
                            <p class="text-xl text-white/90">
                                diselenggarakan oleh <strong>{{ event.eo_name ?? '-' }}</strong>
                            </p>
                            <div class="grid grid-cols-1 gap-3 pt-2 sm:grid-cols-3 sm:gap-6">
                                <div class="flex items-center gap-2 text-sm text-white/90">
                                    <MapPin class="h-5 w-5 shrink-0 text-orange-200" />
                                    <span class="line-clamp-2">{{ event.location ?? 'Lokasi akan diumumkan' }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-white/90">
                                    <Calendar class="h-5 w-5 shrink-0 text-orange-200" />
                                    <span>
                                        {{ formatDate(event.start_date) }}
                                        <span v-if="event.end_date && event.end_date !== event.start_date"> - {{ formatDate(event.end_date) }}</span>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-white/90">
                                    <Users class="h-5 w-5 shrink-0 text-orange-200" />
                                    <span>{{ event.total_registrations ?? 0 }} pendaftar</span>
                                </div>
                            </div>
                        </div>
                        <div class="shrink-0 w-full sm:w-auto">
                            <template v-if="event.is_open_for_registration">
                                <template v-if="has_existing_registration">
                                    <Link v-if="user?.role?.value === 'rider' || user?.role === 'rider'" :href="route('rider.registrations.index')">
                                        <Button size="lg" variant="outline" class="w-full sm:w-auto border-white bg-white text-orange-600 hover:bg-orange-50 hover:text-orange-700 font-semibold">
                                            Lihat Status Pendaftaran
                                        </Button>
                                    </Link>
                                    <Link v-else-if="user" :href="route('dashboard')">
                                        <Button size="lg" variant="outline" class="w-full sm:w-auto border-white bg-white text-orange-600 hover:bg-orange-50 hover:text-orange-700 font-semibold">
                                            Cek di Dashboard
                                        </Button>
                                    </Link>
                                    <Link v-else :href="route('events.show', event.slug) + '#pendaftaran-saya'" class="block">
                                        <Button size="lg" variant="outline" class="w-full sm:w-auto border-white bg-white text-orange-600 hover:bg-orange-50 hover:text-orange-700 font-semibold">
                                            <CheckCircle2 class="mr-2 h-5 w-5" /> Anda Sudah Terdaftar
                                        </Button>
                                    </Link>
                                </template>
                                <template v-else>
                                    <Link :href="route('events.register.form', event.slug)">
                                        <Button size="lg" class="w-full sm:w-auto border-white bg-white text-orange-600 hover:bg-orange-50 hover:text-orange-700 font-semibold shadow-lg">
                                            <Trophy class="mr-2 h-5 w-5" /> DAFTAR SEKARANG
                                        </Button>
                                    </Link>
                                </template>
                            </template>
                            <template v-else>
                                <Badge variant="outline" class="w-full sm:w-auto border-white/60 bg-white/10 text-white px-4 py-2 justify-center text-sm backdrop-blur-sm">
                                    <Clock class="mr-2 h-4 w-4" /> Pendaftaran saat ini ditutup
                                </Badge>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-10 grid gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-8">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-xl">
                                <Trophy class="h-5 w-5 text-orange-500" />
                                Tentang Event
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-if="event.description" class="whitespace-pre-wrap text-foreground leading-relaxed">
                                {{ event.description }}
                            </div>
                            <p v-else class="text-muted-foreground italic">
                                Deskripsi event akan segera ditambahkan oleh penyelenggara.
                            </p>
                            <Separator />
                            <div class="grid gap-3 md:grid-cols-3 pt-2">
                                <div class="rounded-lg bg-muted/40 p-4">
                                    <div class="flex items-center gap-2 text-sm text-muted-foreground mb-1"><Users class="h-4 w-4" /> Total Pendaftar</div>
                                    <div class="text-2xl font-bold">{{ event.total_registrations ?? 0 }}</div>
                                </div>
                                <div class="rounded-lg bg-muted/40 p-4">
                                    <div class="flex items-center gap-2 text-sm text-muted-foreground mb-1"><Layers class="h-4 w-4" /> Jumlah Kelas</div>
                                    <div class="text-2xl font-bold">{{ event.race_classes?.length ?? 0 }}</div>
                                </div>
                                <div class="rounded-lg bg-muted/40 p-4">
                                    <div class="flex items-center gap-2 text-sm text-muted-foreground mb-1"><ShieldCheck class="h-4 w-4" /> Penyelenggara</div>
                                    <div class="text-lg font-bold truncate">{{ event.eo_name ?? event.organizer?.name ?? '-' }}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-xl">
                                <Layers class="h-5 w-5 text-orange-500" />
                                Kelas Race Tersedia
                                <Badge variant="outline" class="ml-auto">
                                    {{ event.race_classes?.length ?? 0 }} Kelas
                                </Badge>
                            </CardTitle>
                            <CardDescription>
                                Pilih kelas yang sesuai dengan kemampuan dan kategori motor Anda. Bisa pilih lebih dari satu kelas!
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="event.race_classes && event.race_classes.length > 0" class="grid gap-4 md:grid-cols-2">
                                <Card v-for="rc in event.race_classes" :key="rc.id" class="group relative overflow-hidden transition-all hover:shadow-md border-muted">
                                    <CardHeader class="pb-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <CardTitle class="text-lg flex items-center gap-2">
                                                    <Trophy class="h-4 w-4 text-orange-500" />
                                                    {{ rc.name }}
                                                </CardTitle>
                                                <CardDescription class="mt-1 flex items-center gap-1.5">
                                                    <code class="rounded bg-muted px-1.5 py-0.5 text-[11px]">{{ rc.code }}</code>
                                                    <template v-if="rc.level_order !== undefined && rc.level_order !== null">
                                                        <span class="text-[11px]">Level #{{ rc.level_order }}</span>
                                                    </template>
                                                </CardDescription>
                                            </div>
                                            <Badge v-if="rc.is_full" variant="destructive" class="shrink-0 gap-1 text-[11px]">
                                                <XCircle class="h-3 w-3" /> PENUH
                                            </Badge>
                                            <Badge v-else class="shrink-0 gap-1 bg-green-100 text-green-700 hover:bg-green-100 border-0 dark:bg-green-900/40 dark:text-green-300 text-[11px]">
                                                <CheckCircle2 class="h-3 w-3" /> Buka
                                            </Badge>
                                        </div>
                                    </CardHeader>
                                    <Separator />
                                    <CardContent class="pt-4 space-y-3">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-muted-foreground flex items-center gap-1.5"><Users class="h-3.5 w-3.5" /> Kuota</span>
                                            <span class="font-medium">
                                                <template v-if="rc.quota !== null">
                                                    {{ rc.remaining_quota ?? rc.quota }} / {{ rc.quota }} slot tersisa
                                                </template>
                                                <template v-else>Tanpa batas</template>
                                            </span>
                                        </div>
                                        <Separator />
                                        <div class="flex items-center justify-between pt-1">
                                            <span class="text-muted-foreground text-sm flex items-center gap-1.5"><DollarSign class="h-3.5 w-3.5" /> Biaya</span>
                                            <span class="font-black text-xl text-orange-600 dark:text-orange-500">
                                                {{ rc.price_formatted ?? formatCurrency(rc.price) }}
                                            </span>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                            <div v-else class="text-center py-12">
                                <Layers class="mx-auto mb-3 h-10 w-10 text-gray-400 opacity-50" />
                                <p class="font-medium text-gray-900 dark:text-white">Kelas akan segera diumumkan</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Penyelenggara masih menyusun kategori kelas race.</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-emerald-200/60 dark:border-emerald-900/60 shadow-md overflow-hidden">
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-xl">
                                <Flag class="h-5 w-5 text-emerald-600 dark:text-emerald-500" />
                                Daftar Peserta (Entry List)
                                <Badge variant="outline" class="ml-auto border-emerald-400 text-emerald-700 dark:border-emerald-800 dark:text-emerald-400">
                                    {{ totalEntryListRiders }} Peserta
                                </Badge>
                            </CardTitle>
                            <CardDescription>
                                Hanya pendaftar dengan status <strong>Entry List</strong> (setelah verifikasi admin) yang muncul di sini. Tabel publik <strong>hanya menampilkan 3 kolom wajib</strong> untuk melindungi data pribadi.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="p-0">
                            <template v-if="totalEntryListRiders > 0">
                                <Tabs v-model="entryListActiveTab">
                                    <div class="border-b bg-white/50 dark:bg-gray-900/40 px-1 pt-2">
                                        <TabsList class="bg-transparent p-0 h-auto flex-wrap justify-start gap-1.5 pb-2">
                                            <TabsTrigger
                                                v-for="rc in event.race_classes"
                                                :key="'et-' + rc.id"
                                                :value="rc.id"
                                                class="rounded-full border data-[state=active]:bg-emerald-500 data-[state=active]:text-white data-[state=active]:border-emerald-500 text-xs sm:text-sm"
                                            >
                                                {{ rc.name }}
                                                <span class="ml-1 text-[10px] opacity-70">
                                                    ({{ entryListMap[rc.id]?.length ?? 0 }})
                                                </span>
                                            </TabsTrigger>
                                        </TabsList>
                                    </div>

                                    <TabsContent v-for="rc in event.race_classes" :key="'ec-' + rc.id" :value="rc.id" class="p-0 mt-0">
                                        <div class="rounded-none">
                                            <div class="overflow-x-auto">
                                                <table class="w-full border-collapse">
                                                    <thead class="bg-emerald-50 dark:bg-emerald-950/30 border-b border-emerald-200/60 dark:border-emerald-900/60">
                                                        <tr>
                                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-300">
                                                                Nama Rider
                                                            </th>
                                                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-300">
                                                                Nama Tim
                                                            </th>
                                                            <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-300 w-28">
                                                                Nomor Start
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template v-if="(entryListMap[rc.id]?.length ?? 0) > 0">
                                                            <tr
                                                                v-for="(row, idx) in entryListMap[rc.id]"
                                                                :key="'row-' + rc.id + '-' + idx"
                                                                class="border-b border-gray-100 dark:border-gray-800 hover:bg-emerald-50/30 dark:hover:bg-emerald-950/10 transition-colors"
                                                            >
                                                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                                    {{ row.full_name ?? '-' }}
                                                                </td>
                                                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                                    {{ row.team_name ?? '-' }}
                                                                </td>
                                                                <td class="px-4 py-3 text-center">
                                                                    <span class="inline-flex items-center justify-center rounded-md bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 px-2.5 py-1 font-black font-mono text-sm tracking-wide min-w-[44px]">
                                                                        {{ row.start_number ?? '-' }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        </template>
                                                        <template v-else>
                                                            <tr>
                                                                <td colspan="3" class="px-4 py-10 text-center text-sm text-muted-foreground italic">
                                                                    Belum ada peserta dengan status Entry List untuk kelas {{ rc.name }}.
                                                                </td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </TabsContent>
                                </Tabs>
                            </template>
                            <template v-else>
                                <div class="text-center py-14 px-4">
                                    <Users class="mx-auto h-12 w-12 opacity-40 text-muted-foreground mb-3" />
                                    <div class="text-base font-semibold text-gray-900 dark:text-white">
                                        Entry List belum tersedia
                                    </div>
                                    <p class="text-sm text-muted-foreground mt-1 max-w-lg mx-auto leading-relaxed">
                                        Pendaftaran sedang berjalan. Setelah Admin melakukan verifikasi & approval final, nama rider akan tampil otomatis di halaman ini sesuai kelas masing-masing.
                                    </p>
                                </div>
                            </template>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-6">
                    <Card class="sticky top-24">
                        <CardHeader>
                            <CardTitle class="text-lg">Ringkasan Pendaftaran</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm"><span class="text-muted-foreground">Tanggal Mulai</span><span class="font-medium">{{ formatDate(event.start_date) }}</span></div>
                                <div v-if="event.end_date" class="flex justify-between text-sm"><span class="text-muted-foreground">Tanggal Akhir</span><span class="font-medium">{{ formatDate(event.end_date) }}</span></div>
                                <div class="flex justify-between text-sm"><span class="text-muted-foreground">EO / Penyelenggara</span><span class="font-medium text-right">{{ event.eo_name ?? '-' }}</span></div>
                                <div class="flex justify-between text-sm"><span class="text-muted-foreground">Disiplin</span><span class="font-medium text-right">{{ event.discipline?.name ?? '-' }}</span></div>
                                <Separator />
                                <div class="flex justify-between text-sm items-start"><span class="text-muted-foreground shrink-0">Status Pendaftaran</span><Badge :class="statusColors[event.status] || statusColors.draft">{{ statusLabels[event.status] ?? event.status }}</Badge></div>
                                <Separator />
                                <div class="flex justify-between text-sm items-start"><span class="text-muted-foreground">Biaya Termurah</span>
                                    <span v-if="event.race_classes && event.race_classes.length" class="font-bold text-right text-orange-600 dark:text-orange-500">
                                        {{ Math.min(...event.race_classes.map((c: any) => c.price ?? 0)).toLocaleString('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }) }}
                                    </span>
                                    <span v-else class="font-medium">-</span>
                                </div>
                            </div>

                            <Separator />

                            <template v-if="event.is_open_for_registration">
                                <template v-if="has_existing_registration">
                                    <Link v-if="user?.role?.value === 'rider' || user?.role === 'rider'" :href="route('rider.registrations.index')">
                                        <Button class="w-full bg-emerald-600 hover:bg-emerald-700">Lihat Status Saya</Button>
                                    </Link>
                                    <Link v-else-if="user" :href="route('dashboard')">
                                        <Button class="w-full bg-emerald-600 hover:bg-emerald-700">Sudah Daftar - Cek Dashboard</Button>
                                    </Link>
                                    <Link v-else :href="route('events.show', event.slug) + '#pendaftaran-saya'">
                                        <Button class="w-full bg-emerald-600 hover:bg-emerald-700">
                                            <CheckCircle2 class="mr-2 h-4 w-4" /> Anda Sudah Terdaftar
                                        </Button>
                                    </Link>
                                </template>
                                <template v-else>
                                    <Link :href="route('events.register.form', event.slug)">
                                        <Button class="w-full bg-orange-500 hover:bg-orange-600">
                                            <Trophy class="mr-2 h-4 w-4" /> Isi Form Pendaftaran
                                        </Button>
                                    </Link>
                                </template>
                            </template>
                            <template v-else>
                                <Badge variant="outline" class="w-full justify-center py-2 text-sm gap-1.5 border-orange-300 text-orange-700 dark:border-orange-700 dark:text-orange-400">
                                    <Clock class="h-4 w-4" /> Pendaftaran Belum Dibuka
                                </Badge>
                            </template>

                            <p class="text-[11px] text-muted-foreground text-center pt-2 leading-relaxed">
                                Dengan mendaftar Anda menyetujui syarat & ketentuan event serta kesanggupan mengikuti seluruh rangkaian acara.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <footer class="mt-16 border-t bg-white dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500 text-white">
                        <Trophy class="h-4 w-4" />
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white">
                        Optimum<span class="text-orange-500">Race</span>
                    </span>
                </div>
                <p class="text-sm text-muted-foreground">
                    © {{ new Date().getFullYear() }} Optimum Race. Platform registrasi event otomotif.
                </p>
            </div>
        </footer>
    </div>
</template>

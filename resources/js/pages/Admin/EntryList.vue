<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Toaster } from '@/components/ui/sonner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import route from '@/composables/useRouteHelper';
import type { FlashToast } from '@/types/ui';
import {
    FileSpreadsheet,
    Search,
    Filter,
    FileJson,
    FileText,
    Layers,
    Users,
    Trophy,
    ChevronLeft,
    ChevronRight,
    Calendar,
    User,
    Phone,
    Mail,
    MapPin,
    FolderGit2,
    Hash,
    RefreshCw,
    Eye,
} from 'lucide-vue-next';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Pagination<T> {
    data: T[];
    current_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
    last_page: number;
    links: PaginationLink[];
}

interface EntryRow {
    registration_id: string;
    event_id: string;
    event_title: string;
    status: string;
    status_label: string;
    full_name: string;
    jersey_name: string | null;
    id_number: string;
    phone: string;
    email: string;
    province: string;
    regency: string;
    team_name: string;
    start_number_main: string | null;
    classes: { race_class_id: string; name: string; code: string; price: number }[];
    entry_list_published_at: string | null;
    created_at: string;
}

interface RaceClassOpt {
    id: string;
    event_id: string;
    name: string;
    code: string;
    quota: number | null;
    price: number | string | null;
    event_title: string;
}

interface StatusOpt {
    value: string;
    label: string;
}

interface EventOpt {
    id: string;
    title: string;
    start_date: string;
}

interface SummaryRaceClass {
    id: string;
    name: string;
    code: string;
    count: number;
}

interface Props {
    entries: Pagination<EntryRow>;
    filters: {
        event_id: string | null;
        race_class_ids: string[];
        statuses: string[];
        search: string | null;
        per_page: number;
    };
    events: EventOpt[];
    race_classes: RaceClassOpt[];
    statuses_list: StatusOpt[];
    summary: {
        total_filtered: number;
        by_race_class: SummaryRaceClass[];
    };
}

const props = defineProps<Props>();
const page = usePage<any>();

const RegistrationStatusDefault = 'entry_list';

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

// === Local filter state ===
const selEvent = ref<string | 'all'>(props.filters.event_id || 'all');
const selRaceClass = ref<string | 'all'>(
    (Array.isArray(props.filters.race_class_ids) && props.filters.race_class_ids[0]) || 'all'
);
const selStatuses = ref<string[]>([...(props.filters.statuses || [RegistrationStatusDefault])]);
const searchInput = ref<string>(props.filters.search || '');
const perPage = ref<number>(props.filters.per_page ?? 15);

const filteredRaceClassOptions = computed<RaceClassOpt[]>(() => {
    if (!selEvent.value || selEvent.value === 'all') return props.race_classes;
    return props.race_classes.filter((rc) => rc.event_id === selEvent.value);
});

const toggleStatus = (val: string) => {
    const idx = selStatuses.value.indexOf(val);
    if (idx >= 0) {
        selStatuses.value.splice(idx, 1);
    } else {
        selStatuses.value.push(val);
    }
};

const applyFilters = () => {
    const query: Record<string, any> = {};
    if (selEvent.value && selEvent.value !== 'all') {
        query.event_id = selEvent.value;
    }
    if (selRaceClass.value && selRaceClass.value !== 'all') {
        query.race_class_id = selRaceClass.value;
    }
    if (selStatuses.value.length) {
        query.statuses = selStatuses.value;
    }
    if (searchInput.value.trim()) {
        query.search = searchInput.value.trim();
    }
    query.per_page = perPage.value;
    router.get(route('admin.entry-list.index'), query, {
        preserveState: true,
        replace: true,
        only: ['entries', 'summary', 'filters'],
    });
};

const resetFilters = () => {
    selEvent.value = 'all';
    selRaceClass.value = 'all';
    selStatuses.value = ['entry_list'];
    searchInput.value = '';
    perPage.value = 15;
    applyFilters();
};

// === Export ===
const exporting = ref<string | null>(null);

const buildExportQs = (): string => {
    const q = new URLSearchParams();
    if (selEvent.value && selEvent.value !== 'all') q.set('event_id', selEvent.value);
    if (selRaceClass.value && selRaceClass.value !== 'all') q.set('race_class_id', selRaceClass.value);
    (selStatuses.value || []).forEach((s) => q.append('statuses[]', s));
    if (searchInput.value.trim()) q.set('search', searchInput.value.trim());
    return q.toString();
};

const doExport = async (format: 'csv' | 'xlsx' | 'pdf') => {
    if (exporting.value) return;
    exporting.value = format;
    try {
        const qs = buildExportQs();
        const base = route('admin.entry-list.export', format);
        // Fallback route helper
        const url = (base.includes('{format}') ? base.replace('{format}', format) : base) + (qs ? `?${qs}` : '');
        // Trigger browser download
        window.location.href = url;
        toast.success(`Sedang menyiapkan file ${format.toUpperCase()}...`);
    } finally {
        setTimeout(() => {
            exporting.value = null;
        }, 1500);
    }
};

// === Pagination helpers
const goToPage = (url: string | null) => {
    if (!url) return;
    router.get(url, {}, { preserveState: true, replace: true, only: ['entries', 'summary'] });
};

const statusColor = (status: string): string => {
    switch (status) {
        case 'entry_list':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border-0';
        case 'approved':
            return 'bg-green-100 text-green-700';
        case 'payment_review':
            return 'bg-blue-100 text-blue-700';
        case 'pending_payment':
            return 'bg-amber-100 text-amber-700';
        case 'rejected':
            return 'bg-red-100 text-red-700';
        case 'cancelled':
            return 'bg-slate-100 text-slate-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
};

const formatDate = (iso: string | null): string => {
    if (!iso) return '-';
    try {
        const d = new Date(iso);
        if (Number.isNaN(d.getTime())) return iso;
        return d.toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return iso;
    }
};
</script>

<template>
    <Toaster />
    <Head title="Entry List Export - Optimum Race Admin" />

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl flex items-center gap-2">
                    <FileSpreadsheet class="h-7 w-7 text-orange-500" />
                    Daftar Entry List Peserta
                </h1>
                <p class="mt-1 text-sm text-muted-foreground max-w-2xl leading-relaxed">
                    Filter data peserta berdasarkan event, kelas balap, dan status. Preview hasil filter sebelum diunduh ke format Excel, CSV, atau PDF.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Badge variant="outline" class="gap-1.5 border-emerald-300 text-emerald-700 dark:border-emerald-800 dark:text-emerald-300">
                    <Users class="h-3.5 w-3.5" />
                    Total: {{ summary.total_filtered }} peserta
                </Badge>
            </div>
        </div>

        <!-- Summary Cards -->
        <div v-if="summary.by_race_class && summary.by_race_class.length > 0" class="grid gap-3 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-6">
            <div
                v-for="s in summary.by_race_class.slice(0, 12)"
                :key="s.id"
                class="rounded-xl border bg-card px-3 py-2.5 text-card-foreground shadow-sm"
            >
                <div class="text-[11px] text-muted-foreground truncate" :title="s.name">{{ s.name }}</div>
                <div class="flex items-end justify-between mt-0.5">
                    <div class="text-xl font-black text-orange-600 dark:text-orange-500">{{ s.count }}</div>
                    <Badge variant="outline" class="text-[10px] px-1.5 py-0">{{ s.code }}</Badge>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <Card>
            <CardHeader class="pb-3">
                <CardTitle class="flex items-center gap-2 text-lg">
                    <Filter class="h-4 w-4 text-orange-500" />
                    Filter Data
                </CardTitle>
                <CardDescription>Saring data sesuai kebutuhan sebelum preview / ekspor.</CardDescription>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-12">
                <div class="md:col-span-4 space-y-1.5">
                    <Label>Event</Label>
                    <Select :model-value="selEvent" @update:model-value="(v: any) => { selEvent = v; selRaceClass = 'all'; }">
                        <SelectTrigger>
                            <SelectValue placeholder="Semua Event" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">📋 Semua Event</SelectItem>
                            <SelectItem v-for="e in events" :key="e.id" :value="e.id">
                                <span class="inline-flex items-center gap-2">
                                    <Calendar class="h-3.5 w-3.5 text-muted-foreground" />
                                    {{ e.title }}
                                    <span class="text-[10px] text-muted-foreground">({{ e.start_date }})</span>
                                </span>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="md:col-span-4 space-y-1.5">
                    <Label>Kelas Balap</Label>
                    <Select
                        :model-value="selRaceClass"
                        @update:model-value="(v: any) => { selRaceClass = v; }"
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Semua Kelas Balap" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">🏁 Semua Kelas Balap</SelectItem>
                            <div v-if="filteredRaceClassOptions.length === 0" class="px-2 py-1.5 text-xs text-muted-foreground italic">
                                Pilih event terlebih dahulu atau belum ada kelas.
                            </div>
                            <SelectItem v-for="rc in filteredRaceClassOptions" :key="rc.id" :value="rc.id">
                                <span class="inline-flex items-center gap-2 w-full">
                                    <Layers class="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                                    <span class="truncate flex-1">{{ rc.name }}</span>
                                    <span class="text-[10px] text-muted-foreground shrink-0">({{ rc.code }})</span>
                                </span>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="md:col-span-4 space-y-1.5">
                    <Label>Status Pendaftaran</Label>
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        <label
                            v-for="st in statuses_list"
                            :key="st.value"
                            class="cursor-pointer inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs hover:bg-muted/60 transition-colors"
                            :class="{ 'bg-orange-50 border-orange-300 dark:bg-orange-900/20 dark:border-orange-700': selStatuses.includes(st.value) }"
                        >
                            <Checkbox :checked="selStatuses.includes(st.value)" @change="toggleStatus(st.value)" />
                            {{ st.label }}
                        </label>
                    </div>
                </div>

                <div class="md:col-span-5 space-y-1.5">
                    <Label for="el_search" class="flex items-center gap-1.5">
                        <Search class="h-3.5 w-3.5 text-muted-foreground" /> Pencarian
                    </Label>
                    <Input
                        id="el_search"
                        placeholder="Cari nama rider, NIK, no start, tim, no HP..."
                        v-model="searchInput"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div class="md:col-span-3 space-y-1.5">
                    <Label for="el_perpage">Data / Halaman</Label>
                    <Select :model-value="String(perPage)" @update:model-value="(v: any) => perPage = Number(v)">
                        <SelectTrigger id="el_perpage">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="10">10 baris</SelectItem>
                            <SelectItem value="15">15 baris</SelectItem>
                            <SelectItem value="25">25 baris</SelectItem>
                            <SelectItem value="50">50 baris</SelectItem>
                            <SelectItem value="100">100 baris</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="md:col-span-4 flex items-end gap-2">
                    <Button type="button" class="bg-orange-500 hover:bg-orange-600 flex-1 gap-1.5" @click="applyFilters">
                        <Eye class="h-4 w-4" /> Terapkan &amp; Preview
                    </Button>
                    <Button type="button" variant="outline" class="gap-1.5" @click="resetFilters">
                        <RefreshCw class="h-4 w-4" /> Reset
                    </Button>
                </div>
            </CardContent>

            <Separator />

            <CardFooter class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-muted/30 px-6 py-3 rounded-b-lg">
                <div class="text-xs text-muted-foreground">
                    💡 Setelah filter sesuai, unduh dalam format pilihan Anda:
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button type="button" variant="outline" class="gap-1.5 border-green-500/50 hover:bg-green-50 text-green-700 hover:text-green-800 dark:text-green-300" @click="doExport('xlsx')" :disabled="!!exporting">
                        <FileSpreadsheet v-if="!exporting || exporting !== 'xlsx'" class="h-4 w-4" />
                        <template v-else><RefreshCw class="h-4 w-4 animate-spin" /></template>
                        Excel (.xls)
                    </Button>
                    <Button type="button" variant="outline" class="gap-1.5 border-blue-500/50 hover:bg-blue-50 text-blue-700 hover:text-blue-800 dark:text-blue-300" @click="doExport('csv')" :disabled="!!exporting">
                        <FileText v-if="!exporting || exporting !== 'csv'" class="h-4 w-4" />
                        <template v-else><RefreshCw class="h-4 w-4 animate-spin" /></template>
                        CSV
                    </Button>
                    <Button type="button" variant="outline" class="gap-1.5 border-red-500/50 hover:bg-red-50 text-red-700 hover:text-red-800 dark:text-red-300" @click="doExport('pdf')" :disabled="!!exporting">
                        <FileJson v-if="!exporting || exporting !== 'pdf'" class="h-4 w-4" />
                        <template v-else><RefreshCw class="h-4 w-4 animate-spin" /></template>
                        PDF
                    </Button>
                </div>
            </CardFooter>
        </Card>

        <!-- Preview Table -->
        <Card>
            <CardHeader class="pb-3">
                <CardTitle class="flex items-center gap-2 text-lg">
                    <Users class="h-5 w-5 text-orange-500" />
                    Preview Daftar Peserta
                </CardTitle>
                <CardDescription>
                    Menampilkan <strong>{{ entries.from || 0 }}-{{ entries.to || 0 }}</strong> dari <strong>{{ entries.total }}</strong> data yang difilter.
                </CardDescription>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead class="bg-orange-50 dark:bg-orange-950/30 border-b border-orange-200/60 dark:border-orange-900/60">
                            <tr>
                                <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-orange-900 dark:text-orange-300 w-20">
                                    No Start
                                </th>
                                <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-orange-900 dark:text-orange-300">
                                    Nama Rider
                                </th>
                                <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-orange-900 dark:text-orange-300 hidden md:table-cell">
                                    <span class="inline-flex items-center gap-1"><FolderGit2 class="h-3 w-3" /> Tim</span>
                                </th>
                                <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-orange-900 dark:text-orange-300">
                                    <span class="inline-flex items-center gap-1"><Trophy class="h-3 w-3" /> Kelas</span>
                                </th>
                                <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-orange-900 dark:text-orange-300 hidden lg:table-cell">
                                    <span class="inline-flex items-center gap-1"><Calendar class="h-3 w-3" /> Event</span>
                                </th>
                                <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-orange-900 dark:text-orange-300 w-28">
                                    Status
                                </th>
                                <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-orange-900 dark:text-orange-300 w-40 hidden 2xl:table-cell">
                                    Kontak
                                </th>
                                <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-orange-900 dark:text-orange-300 w-36 hidden xl:table-cell">
                                    <span class="inline-flex items-center gap-1"><MapPin class="h-3 w-3" /> Asal</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="entries.data && entries.data.length > 0">
                                <tr
                                    v-for="(row, idx) in entries.data"
                                    :key="row.registration_id + '-' + idx"
                                    class="border-b border-gray-100 dark:border-gray-800 hover:bg-orange-50/40 dark:hover:bg-orange-950/10 transition-colors"
                                >
                                    <td class="px-3 py-3 align-top">
                                        <span v-if="row.start_number_main" class="inline-flex items-center justify-center rounded-md bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 px-2 py-1 font-black font-mono text-sm min-w-[40px]">
                                            {{ row.start_number_main }}
                                        </span>
                                        <span v-else class="text-muted-foreground text-xs italic">-</span>
                                    </td>
                                    <td class="px-3 py-3 align-top">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">
                                            <User class="inline h-3.5 w-3.5 text-muted-foreground mr-1 -mt-0.5" />
                                            {{ row.full_name || '-' }}
                                        </div>
                                        <div v-if="row.jersey_name" class="text-xs text-orange-600 dark:text-orange-400 mt-0.5">
                                            Jersey: <strong>{{ row.jersey_name }}</strong>
                                        </div>
                                        <div class="text-[11px] text-muted-foreground mt-1">
                                            <Hash class="inline h-3 w-3 mr-0.5 -mt-0.5" />
                                            NIK/KTA: {{ row.id_number || '-' }}
                                        </div>
                                        <div class="mt-1.5">
                                            <Link
                                                :href="route('admin.registrations.show', row.registration_id)"
                                                class="text-[11px] text-blue-600 hover:underline dark:text-blue-400"
                                            >
                                                Lihat detail →
                                            </Link>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 align-top hidden md:table-cell text-sm">
                                        {{ row.team_name || '-' }}
                                    </td>
                                    <td class="px-3 py-3 align-top">
                                        <div class="flex flex-wrap gap-1">
                                            <Badge
                                                v-for="c in row.classes"
                                                :key="c.race_class_id + '-b'"
                                                variant="outline"
                                                class="text-[11px] border-slate-300 dark:border-slate-700"
                                            >
                                                {{ c.name }}
                                            </Badge>
                                            <span v-if="!row.classes || row.classes.length === 0" class="text-[11px] text-muted-foreground italic">-</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 align-top hidden lg:table-cell text-sm">
                                        <div class="font-medium truncate max-w-[220px]" :title="row.event_title">
                                            {{ row.event_title }}
                                        </div>
                                        <div class="text-[10px] text-muted-foreground mt-0.5">
                                            Masuk Entry List: {{ formatDate(row.entry_list_published_at) }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 align-top">
                                        <Badge :class="['text-[11px] border-0', statusColor(row.status)]">
                                            {{ row.status_label }}
                                        </Badge>
                                    </td>
                                    <td class="px-3 py-3 align-top hidden 2xl:table-cell text-[11px]">
                                        <div class="flex items-center gap-1 text-muted-foreground"><Phone class="h-3 w-3" /> <span>{{ row.phone || '-' }}</span></div>
                                        <div class="flex items-center gap-1 text-muted-foreground mt-0.5"><Mail class="h-3 w-3" /> <span class="truncate max-w-[170px]" :title="row.email">{{ row.email || '-' }}</span></div>
                                    </td>
                                    <td class="px-3 py-3 align-top hidden xl:table-cell text-[11px] text-muted-foreground">
                                        {{ row.regency || '-' }}<span v-if="row.regency && row.province">, </span>{{ row.province || '' }}
                                    </td>
                                </tr>
                            </template>
                            <template v-else>
                                <tr>
                                    <td colspan="8" class="px-4 py-16 text-center">
                                        <Users class="mx-auto h-10 w-10 opacity-40 text-muted-foreground mb-2" />
                                        <div class="text-base font-semibold text-gray-900 dark:text-white">
                                            Tidak ada data yang cocok dengan filter
                                        </div>
                                        <p class="text-sm text-muted-foreground mt-1 max-w-lg mx-auto leading-relaxed">
                                            Ubah filter pilihan Anda atau reset filter untuk menampilkan semua data.
                                        </p>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </CardContent>

            <!-- Pagination -->
            <div v-if="entries.last_page > 1" class="border-t px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="text-xs text-muted-foreground text-center sm:text-left">
                    Menampilkan {{ entries.from }} sampai {{ entries.to }} dari {{ entries.total }} data
                </div>
                <div class="flex items-center justify-center gap-1">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        class="gap-1"
                        :disabled="entries.current_page <= 1"
                        @click="goToPage(entries.links?.[0]?.url || null)"
                    >
                        <ChevronLeft class="h-4 w-4" /> Sebelumnya
                    </Button>
                    <div class="hidden md:flex items-center gap-1">
                        <button
                            v-for="(link, linkIdx) in (entries.links?.slice(1, entries.links.length - 1) || [])"
                            :key="'pl-' + linkIdx"
                            type="button"
                            class="inline-flex h-8 min-w-[32px] items-center justify-center rounded-md border text-xs"
                            :class="link.active ? 'bg-orange-500 text-white border-orange-500' : 'hover:bg-muted/60'"
                            :disabled="!link.url && !link.active"
                            @click="link.url && goToPage(link.url)"
                            v-html="link.label"
                        >
                        </button>
                    </div>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        class="gap-1"
                        :disabled="entries.current_page >= entries.last_page"
                        @click="goToPage(entries.links?.[entries.links.length - 1]?.url || null)"
                    >
                        Selanjutnya <ChevronRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </Card>
    </div>
</template>

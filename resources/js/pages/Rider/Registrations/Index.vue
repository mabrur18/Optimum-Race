<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Search, Eye, Calendar, Layers, DollarSign, AlertTriangle, Trophy } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';

interface RegistrationRow {
    id: string;
    status: string;
    status_label: string;
    status_color: string;
    created_at: string;
    total_amount: number;
    event?: {
        id: string;
        title: string;
        location?: string | null;
        start_date?: string | null;
        discipline?: { name: string } | null;
    } | null;
    items?: { race_class?: { id: string; name: string; code: string } }[];
}

interface Pagination {
    data: RegistrationRow[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    links?: any[];
    from?: number | null;
    to?: number | null;
}

interface Props {
    registrations: Pagination;
    filters: {
        status?: string;
        search?: string;
    };
    statuses_list: { value: string; label: string; color: string }[];
    no_rider_profile?: boolean;
}

const props = defineProps<Props>();

const search = ref(props.filters.search ?? '');
const statusVal = ref(props.filters.status ? props.filters.status : '__all__');

const debouncedSearch = debounce(() => {
    const sq = statusVal.value === '__all__' ? '' : statusVal.value;
    router.get(
        route('rider.registrations.index'),
        { search: search.value, status: sq },
        { preserveState: true, preserveScroll: true }
    );
}, 300);

watch([search, statusVal], () => {
    debouncedSearch();
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

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
    <Head title="Registrasi Saya" />

    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Registrasi Saya</h1>
                <p class="text-muted-foreground">Lihat semua riwayat pendaftaran event dan status terbaru.</p>
            </div>
            <Link :href="route('home')">
                <Button class="bg-orange-500 hover:bg-orange-600 gap-2">
                    <Trophy class="h-4 w-4" />
                    Cari Event Lain
                </Button>
            </Link>
        </div>

        <div v-if="no_rider_profile" class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 flex items-start gap-3 dark:border-yellow-800 dark:bg-yellow-900/20">
            <AlertTriangle class="h-5 w-5 text-yellow-600 dark:text-yellow-400 shrink-0 mt-0.5" />
            <div class="text-sm text-yellow-800 dark:text-yellow-300">
                <p class="font-medium">Anda belum memiliki profil rider.</p>
                <p class="mt-0.5">Silakan daftar event terlebih dahulu agar profil rider Anda dibuat otomatis.</p>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Filter</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="search">Cari Event</Label>
                        <div class="relative">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                            <Input id="search" v-model="search" placeholder="Nama event, lokasi..." class="pl-10" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="st">Status</Label>
                        <Select v-model="statusVal">
                            <SelectTrigger><SelectValue placeholder="Semua Status" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua Status</SelectItem>
                                <SelectItem v-for="st in statuses_list" :key="st.value" :value="st.value">{{ st.label }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Event</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Kelas Diikuti</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Tanggal Event</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Waktu Daftar</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Total Biaya</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Status</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="reg in registrations.data" :key="reg.id" class="border-b hover:bg-muted/50">
                                <td class="px-4 py-3">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 text-sm">
                                            <Calendar class="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                                            <span class="font-medium">{{ reg.event?.title ?? '-' }}</span>
                                        </div>
                                        <p v-if="reg.event?.location" class="text-xs text-muted-foreground ml-5.5">
                                            {{ reg.event.location }}
                                        </p>
                                        <p v-if="reg.event?.discipline?.name" class="text-xs text-muted-foreground ml-5.5">
                                            {{ reg.event.discipline?.name }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="reg.items && reg.items.length > 0" class="space-y-1">
                                        <div v-for="(item, idx) in reg.items" :key="'i-' + idx" class="flex items-center gap-2 text-sm">
                                            <Layers class="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                                            <span>{{ item.race_class?.name ?? 'Kelas tidak ditemukan' }}</span>
                                            <code v-if="item.race_class?.code" class="rounded bg-muted px-1.5 py-0.5 text-[10px] text-muted-foreground">
                                                {{ item.race_class.code }}
                                            </code>
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-muted-foreground italic">-</span>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ formatDate(reg.event?.start_date) }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ formatDate(reg.created_at) }}</td>
                                <td class="px-4 py-3 text-right font-medium text-sm flex items-center justify-end gap-1">
                                    <DollarSign class="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                                    {{ formatCurrency(reg.total_amount) }}
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :class="statusColors[reg.status] || statusColors.draft">
                                        {{ reg.status_label }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('rider.registrations.show', reg.id)">
                                        <Button variant="ghost" size="sm" class="gap-1.5">
                                            <Eye class="h-4 w-4" />
                                            Detail
                                        </Button>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="registrations.data.length === 0" class="text-center py-12">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                            <Trophy class="h-10 w-10 text-gray-400" />
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Belum ada pendaftaran</h3>
                        <p class="mt-2 text-gray-500 dark:text-gray-400">Belum ada riwayat pendaftaran event. Yuk, daftar event pilihan Anda sekarang!</p>
                        <div class="mt-5">
                            <Link :href="route('home')">
                                <Button class="bg-orange-500 hover:bg-orange-600">
                                    Lihat Event Tersedia
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-if="registrations.last_page > 1" class="border-t px-4 py-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">
                            Menampilkan {{ registrations.from }} sampai {{ registrations.to }} dari {{ registrations.total }} pendaftaran
                        </p>
                        <div class="flex items-center gap-2">
                            <Link
                                v-for="(link, idx) in registrations.links ?? []"
                                :key="'regpag-' + idx"
                                :href="link.url || ''"
                                :class="[
                                    'px-3 py-1 text-sm rounded-md transition-colors',
                                    link.active
                                        ? 'bg-orange-500 text-white'
                                        : 'hover:bg-muted',
                                    !link.url && 'pointer-events-none opacity-50',
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

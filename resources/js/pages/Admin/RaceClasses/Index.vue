<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Search, Edit, Trash2, Layers, Calendar, DollarSign, Users } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';

interface RaceClass {
    id: string;
    name: string;
    code: string;
    level_order: number;
    quota: number | null;
    price: number;
    event?: {
        id: string;
        title: string;
        discipline?: { name: string } | null;
    } | null;
    template?: { id: string; name: string } | null;
}

interface Pagination {
    data: RaceClass[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    links?: any[];
    from?: number | null;
    to?: number | null;
}

interface Props {
    race_classes: Pagination;
    filters: {
        event_id?: string;
        search?: string;
    };
    events_list: { id: string; title: string }[];
}

const props = defineProps<Props>();

const search = ref(props.filters.search ?? '');
const eventId = ref(props.filters.event_id ?? '__all__');

const debouncedSearch = debounce(() => {
    const eventQuery = eventId.value === '__all__' ? '' : eventId.value;
    router.get(
        route('admin.race-classes.index'),
        { search: search.value, event_id: eventQuery },
        { preserveState: true, preserveScroll: true }
    );
}, 300);

watch([search, eventId], () => {
    debouncedSearch();
});

const deleteClass = (rc: RaceClass) => {
    if (confirm(`Apakah Anda yakin ingin menghapus kelas "${rc.name}"?`)) {
        router.delete(route('admin.race-classes.destroy', rc.id));
    }
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const createForEventHref = () => {
    const base = route('admin.race-classes.create');
    if (eventId.value && eventId.value !== '__all__') {
        return `${base}?event_id=${eventId.value}`;
    }
    return base;
};
</script>

<template>
    <Head title="Kelas Race" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Manajemen Kelas Race</h1>
                <p class="text-muted-foreground">Kelola kelas balap per event (kuota, harga, kode).</p>
            </div>
            <Link :href="createForEventHref()">
                <Button class="bg-orange-500 hover:bg-orange-600">
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Kelas
                </Button>
            </Link>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Filter</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="search">Cari</Label>
                        <div class="relative">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="search"
                                v-model="search"
                                placeholder="Cari nama kelas atau kode..."
                                class="pl-10"
                            />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="event_filter">Event</Label>
                        <Select v-model="eventId">
                            <SelectTrigger>
                                <SelectValue placeholder="Semua Event" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua Event</SelectItem>
                                <SelectItem
                                    v-for="ev in events_list"
                                    :key="ev.id"
                                    :value="ev.id"
                                >
                                    {{ ev.title }}
                                </SelectItem>
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
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Kelas</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Event</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Urutan</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Kuota</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Biaya</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="rc in race_classes.data" :key="rc.id" class="border-b hover:bg-muted/50">
                                <td class="px-4 py-3">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30">
                                                <Layers class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                                            </div>
                                            <p class="font-medium">{{ rc.name }}</p>
                                        </div>
                                        <code class="rounded bg-muted px-2 py-0.5 text-xs ml-10">{{ rc.code }}</code>
                                        <div v-if="rc.template" class="ml-10">
                                            <Badge variant="outline" class="text-xs">Template: {{ rc.template?.name ?? '-' }}</Badge>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2 text-sm">
                                        <Calendar class="h-3.5 w-3.5 text-muted-foreground" />
                                        <span>{{ rc.event?.title ?? '-' }}</span>
                                        <span v-if="rc.event?.discipline?.name" class="text-muted-foreground">({{ rc.event.discipline?.name }})</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">#{{ rc.level_order }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2 text-sm">
                                        <Users class="h-3.5 w-3.5 text-muted-foreground" />
                                        <span>{{ rc.quota !== null ? `${rc.quota} peserta` : 'Tidak terbatas' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <DollarSign class="h-3.5 w-3.5 text-muted-foreground" />
                                        {{ formatCurrency(rc.price) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('admin.race-classes.edit', rc.id)">
                                            <Button variant="ghost" size="icon">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="text-red-600 hover:text-red-700"
                                            @click="deleteClass(rc)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="race_classes.data.length === 0" class="text-center py-12">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                            <Layers class="h-10 w-10 text-gray-400" />
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Belum ada kelas race</h3>
                        <p class="mt-2 text-gray-500 dark:text-gray-400">Buat kelas race pertama untuk event Anda.</p>
                    </div>
                </div>

                <div v-if="race_classes.last_page > 1" class="border-t px-4 py-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">
                            Menampilkan {{ race_classes.from }} sampai {{ race_classes.to }} dari {{ race_classes.total }} kelas
                        </p>
                        <div class="flex items-center gap-2">
                            <Link
                                v-for="(link, idx) in race_classes.links ?? []"
                                :key="'pag-' + idx"
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

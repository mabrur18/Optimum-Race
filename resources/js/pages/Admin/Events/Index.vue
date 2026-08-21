<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Calendar, MapPin, Plus, Search, Eye, Edit, Trash2, Trophy } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';

interface Event {
    id: string;
    title: string;
    slug: string;
    location: string;
    start_date: string;
    end_date: string | null;
    eo_name: string;
    status: string;
    discipline: {
        name: string;
    };
}

interface Pagination {
    data: Event[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

interface Props {
    events: Pagination;
    filters: {
        status?: string;
        search?: string;
    };
    statuses: { value: string; label: string }[];
}

const props = defineProps<Props>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ? props.filters.status : '__all__');

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    published: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    closed: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    archived: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300',
};

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    published: 'Dibuka',
    closed: 'Ditutup',
    archived: 'Diarsipkan',
};

const debouncedSearch = debounce(() => {
    const statusQuery = status.value === '__all__' ? '' : status.value;
    router.get(
        route('admin.events.index'),
        { search: search.value, status: statusQuery },
        { preserveState: true, preserveScroll: true }
    );
}, 300);

watch([search, status], () => {
    debouncedSearch();
});

const deleteEvent = (event: Event) => {
    if (confirm(`Apakah Anda yakin ingin menghapus event "${event.title}"?`)) {
        router.delete(route('admin.events.destroy', event.id));
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Manajemen Event" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Manajemen Event</h1>
                <p class="text-muted-foreground">Kelola event balap untuk organizer Anda.</p>
            </div>
            <Link :href="route('admin.events.create')">
                <Button class="bg-orange-500 hover:bg-orange-600">
                    <Plus class="mr-2 h-4 w-4" />
                    Buat Event
                </Button>
            </Link>
        </div>

        <!-- Filters -->
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
                                placeholder="Cari event, lokasi, atau EO..."
                                class="pl-10"
                            />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="status">Status</Label>
                        <Select v-model="status">
                            <SelectTrigger>
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua Status</SelectItem>
                                <SelectItem
                                    v-for="statusOption in statuses"
                                    :key="statusOption.value"
                                    :value="statusOption.value"
                                >
                                    {{ statusOption.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Events Table -->
        <Card>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Event</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Disiplin</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Lokasi & Tanggal</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Status</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="event in events.data" :key="event.id" class="border-b hover:bg-muted/50">
                                <td class="px-4 py-3">
                                    <div class="space-y-1">
                                        <p class="font-medium">{{ event.title }}</p>
                                        <p class="text-sm text-muted-foreground">{{ event.eo_name }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <Trophy class="h-4 w-4 text-orange-500" />
                                        <span class="text-sm">{{ event.discipline?.name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="space-y-1 text-sm">
                                        <div class="flex items-center gap-2 text-muted-foreground">
                                            <MapPin class="h-3.5 w-3.5" />
                                            {{ event.location }}
                                        </div>
                                        <div class="flex items-center gap-2 text-muted-foreground">
                                            <Calendar class="h-3.5 w-3.5" />
                                            {{ formatDate(event.start_date) }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :class="statusColors[event.status]">
                                        {{ statusLabels[event.status] }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('admin.events.show', event.id)">
                                            <Button variant="ghost" size="icon">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Link :href="route('admin.events.edit', event.id)">
                                            <Button variant="ghost" size="icon">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="text-red-600 hover:text-red-700"
                                            @click="deleteEvent(event)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="events.last_page > 1" class="border-t px-4 py-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">
                            Menampilkan {{ events.from }} sampai {{ events.to }} dari {{ events.total }} event
                        </p>
                        <div class="flex items-center gap-2">
                            <Link
                                v-for="link in events.links"
                                :key="link.label"
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

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Calendar, MapPin, Plus, Trophy, Users } from 'lucide-vue-next';

interface Stats {
    total_events: number;
    published_events: number;
    total_race_classes: number;
    total_registrations: number;
}

interface RecentEvent {
    id: string;
    title: string;
    status: string;
    start_date: string;
    discipline: {
        name: string;
    };
}

interface Props {
    stats: Stats;
    recent_events: RecentEvent[];
}

const props = defineProps<Props>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    published: 'Dibuka',
    closed: 'Ditutup',
    archived: 'Diarsipkan',
};

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    published: 'bg-green-100 text-green-800',
    closed: 'bg-orange-100 text-orange-800',
    archived: 'bg-slate-100 text-slate-800',
};
</script>

<template>
    <Head title="Dashboard Admin" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
                <p class="text-muted-foreground">Selamat datang di panel admin Optimum Race.</p>
            </div>
            <Link :href="route('admin.events.create')">
                <Button class="bg-orange-500 hover:bg-orange-600">
                    <Plus class="mr-2 h-4 w-4" />
                    Buat Event
                </Button>
            </Link>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Event</CardTitle>
                    <Calendar class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total_events }}</div>
                    <p class="text-xs text-muted-foreground">Semua status event</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Event Dibuka</CardTitle>
                    <Trophy class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.published_events }}</div>
                    <p class="text-xs text-muted-foreground">Siap menerima pendaftaran</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Kelas</CardTitle>
                    <MapPin class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total_race_classes }}</div>
                    <p class="text-xs text-muted-foreground">Kelas race yang tersedia</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Pendaftaran</CardTitle>
                    <Users class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total_registrations }}</div>
                    <p class="text-xs text-muted-foreground">Total pendaftaran masuk</p>
                </CardContent>
            </Card>
        </div>

        <!-- Recent Events -->
        <Card>
            <CardHeader>
                <CardTitle>Event Terbaru</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="recent_events.length > 0" class="space-y-4">
                    <div
                        v-for="event in recent_events"
                        :key="event.id"
                        class="flex items-center justify-between rounded-lg border p-4"
                    >
                        <div class="space-y-1">
                            <p class="font-medium">{{ event.title }}</p>
                            <div class="flex items-center gap-4 text-sm text-muted-foreground">
                                <span class="flex items-center gap-1">
                                    <Trophy class="h-3.5 w-3.5" />
                                    {{ event.discipline?.name ?? '-' }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <Calendar class="h-3.5 w-3.5" />
                                    {{ formatDate(event.start_date) }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', statusColors[event.status]]">
                                {{ statusLabels[event.status] }}
                            </span>
                            <Link :href="route('admin.events.show', event.id)">
                                <Button variant="ghost" size="sm">Detail</Button>
                            </Link>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-8">
                    <p class="text-muted-foreground">Belum ada event. Buat event pertama Anda!</p>
                    <Link :href="route('admin.events.create')" class="mt-4 inline-block">
                        <Button class="bg-orange-500 hover:bg-orange-600">Buat Event</Button>
                    </Link>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

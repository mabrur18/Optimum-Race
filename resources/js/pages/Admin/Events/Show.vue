<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Calendar, ChevronLeft, Edit, MapPin, Trophy, Users, DollarSign, Layers } from 'lucide-vue-next';

interface RaceClass {
    id: string;
    name: string;
    code: string;
    level_order: number;
    quota: number | null;
    price: number;
}

interface Registration {
    id: string;
    status: string;
    created_at: string;
    rider: {
        full_name: string;
        jersey_name: string | null;
    } | null;
}

interface EventDetail {
    id: string;
    title: string;
    slug: string;
    location: string;
    start_date: string;
    end_date: string | null;
    eo_name: string;
    status: string;
    description: string | null;
    discipline: {
        name: string;
    };
    organizer: {
        name: string;
    };
    race_classes: RaceClass[];
    registrations: Registration[];
}

interface Props {
    event: EventDetail;
}

const props = defineProps<Props>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    published: 'Dibuka',
    closed: 'Ditutup',
    archived: 'Diarsipkan',
};

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    published: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    closed: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    archived: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300',
};

const regStatusLabels: Record<string, string> = {
    draft: 'Draft',
    pending_payment: 'Menunggu Pembayaran',
    payment_review: 'Pembayaran Dicek',
    rejected: 'Ditolak',
    approved: 'Disetujui',
    entry_list: 'Entry List',
    cancelled: 'Dibatalkan',
};

const regStatusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    pending_payment: 'bg-yellow-100 text-yellow-800',
    payment_review: 'bg-blue-100 text-blue-800',
    rejected: 'bg-red-100 text-red-800',
    approved: 'bg-green-100 text-green-800',
    entry_list: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-slate-100 text-slate-800',
};
</script>

<template>
    <Head :title="event.title" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <Link :href="route('admin.events.index')">
                <Button variant="outline" size="icon">
                    <ChevronLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div class="flex-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-bold tracking-tight">{{ event.title }}</h1>
                    <Badge :class="statusColors[event.status]">
                        {{ statusLabels[event.status] }}
                    </Badge>
                </div>
                <p class="text-muted-foreground">{{ event.eo_name ?? '-' }} - Disiplin {{ event.discipline?.name ?? 'Umum' }}</p>
            </div>
            <Link :href="route('admin.events.edit', event.id)">
                <Button variant="default" class="bg-orange-500 hover:bg-orange-600">
                    <Edit class="mr-2 h-4 w-4" />
                    Edit Event
                </Button>
            </Link>
        </div>

        <!-- Event Info -->
        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Summary -->
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Informasi Event</CardTitle>
                    <CardDescription>Detail lengkap event balap Anda.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30">
                                <Trophy class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Disiplin</p>
                                <p class="font-medium">{{ event.discipline?.name ?? 'Umum' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30">
                                <Users class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Penyelenggara</p>
                                <p class="font-medium">{{ event.eo_name ?? event.organizer?.name ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 md:col-span-2">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30">
                                <MapPin class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Lokasi</p>
                                <p class="font-medium">{{ event.location }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 md:col-span-2">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30">
                                <Calendar class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Tanggal Pelaksanaan</p>
                                <p class="font-medium">
                                    {{ formatDate(event.start_date) }}
                                    <span v-if="event.end_date"> - {{ formatDate(event.end_date) }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-if="event.description" class="pt-4 border-t">
                        <p class="text-sm text-muted-foreground mb-2">Deskripsi</p>
                        <p class="whitespace-pre-wrap">{{ event.description }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Quick Stats -->
            <div class="space-y-6">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base">Kelas Race</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30">
                                <Layers class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div>
                                <p class="text-3xl font-bold">{{ event.race_classes.length }}</p>
                                <p class="text-sm text-muted-foreground">Total kelas</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base">Pendaftaran</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30">
                                <Users class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div>
                                <p class="text-3xl font-bold">{{ event.registrations.length }}</p>
                                <p class="text-sm text-muted-foreground">Pendaftar masuk</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base">Opsi</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Link :href="`/events/${event.slug}`">
                            <Button variant="outline" class="w-full">
                                Lihat Halaman Publik
                            </Button>
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Race Classes Table -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle>Kelas Race Tersedia</CardTitle>
                        <CardDescription>Daftar kelas yang bisa diikuti peserta.</CardDescription>
                    </div>
                    <Button variant="outline" size="sm" disabled>
                        Tambah Kelas
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <div v-if="event.race_classes.length > 0" class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Kelas</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Kode</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Urutan</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Kuota</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="raceClass in event.race_classes" :key="raceClass.id" class="border-b">
                                <td class="px-4 py-3 font-medium">{{ raceClass.name }}</td>
                                <td class="px-4 py-3">
                                    <code class="rounded bg-muted px-2 py-0.5 text-sm">{{ raceClass.code }}</code>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ raceClass.level_order }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">
                                    {{ raceClass.quota ?? 'Tidak terbatas' }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium">{{ formatCurrency(raceClass.price) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-8">
                    <p class="text-muted-foreground">Belum ada kelas race untuk event ini.</p>
                    <p class="text-sm text-muted-foreground mt-1">Fitur manajemen kelas akan segera hadir.</p>
                </div>
            </CardContent>
        </Card>

        <!-- Registrations Table -->
        <Card>
            <CardHeader>
                <CardTitle>Pendaftar Masuk</CardTitle>
                <CardDescription>
                    Daftar peserta yang telah mendaftar ke event ini.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="event.registrations.length > 0" class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Peserta</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Waktu Daftar</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="reg in event.registrations"
                                :key="reg.id"
                                class="border-b hover:bg-muted/50"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium">
                                        {{ reg.rider?.full_name ?? 'Data rider tidak tersedia' }}
                                    </div>
                                    <div v-if="reg.rider?.jersey_name" class="text-sm text-muted-foreground">
                                        {{ reg.rider.jersey_name }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :class="regStatusColors[reg.status]">
                                        {{ regStatusLabels[reg.status] ?? reg.status }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">
                                    {{ new Date(reg.created_at).toLocaleString('id-ID') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Button variant="ghost" size="sm" disabled>
                                        Detail
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-8">
                    <p class="text-muted-foreground">Belum ada pendaftar untuk event ini.</p>
                    <p class="text-sm text-muted-foreground mt-1">
                        Publish event untuk membuka pendaftaran bagi peserta.
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Building2, Calendar, Trophy, Users } from 'lucide-vue-next';

interface Stats {
    total_organizers: number;
    total_events: number;
    total_registrations: number;
    total_disciplines: number;
}

interface Props {
    stats?: Stats;
}

const props = withDefaults(defineProps<Props>(), {
    stats: () => ({
        total_organizers: 0,
        total_events: 0,
        total_registrations: 0,
        total_disciplines: 4,
    }),
});
</script>

<template>
    <Head title="Dashboard Superadmin" />

    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Dashboard Superadmin</h1>
            <p class="text-muted-foreground">Panel kontrol untuk pengelola seluruh platform Optimum Race.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Organizer</CardTitle>
                    <Building2 class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total_organizers }}</div>
                    <p class="text-xs text-muted-foreground">Event Organizer terdaftar</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Event</CardTitle>
                    <Calendar class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total_events }}</div>
                    <p class="text-xs text-muted-foreground">Semua event lintas organizer</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Pendaftaran</CardTitle>
                    <Users class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total_registrations }}</div>
                    <p class="text-xs text-muted-foreground">Pendaftaran event keseluruhan</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Disiplin Balap</CardTitle>
                    <Trophy class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total_disciplines }}</div>
                    <p class="text-xs text-muted-foreground">Road Race, Enduro, MX, Grasstrack</p>
                </CardContent>
            </Card>
        </div>

        <!-- Platform Notice -->
        <Card class="border-orange-200 dark:border-orange-900 bg-orange-50 dark:bg-orange-950/30">
            <CardHeader>
                <CardTitle class="text-orange-800 dark:text-orange-300 text-base">
                    👋 Selamat Datang di Superadmin Panel
                </CardTitle>
            </CardHeader>
            <CardContent class="text-sm space-y-2 text-orange-900 dark:text-orange-200">
                <p>
                    Di panel ini Anda mengelola seluruh platform. Semua data Organizer dan Event dapat Anda akses di sini.
                </p>
                <div class="pt-2">
                    <Button variant="default" class="bg-orange-500 hover:bg-orange-600">
                        Kelola Organizer
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

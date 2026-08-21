<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Toaster } from '@/components/ui/sonner';
import { dashboard, login, register } from '@/routes';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Calendar, MapPin, Trophy, Users } from 'lucide-vue-next';
import type { FlashToast } from '@/types/ui';

interface Event {
    id: string;
    title: string;
    slug: string;
    location: string;
    start_date: string;
    end_date: string | null;
    eo_name: string | null;
    discipline: {
        name: string;
        slug: string;
    } | null;
}

interface Props {
    events: Event[];
}

const props = defineProps<Props>();
const page = usePage<any>();
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

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};
</script>

<template>
    <Toaster />
    <Head title="Optimum Race - Registrasi Event Otomotif">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-gradient-to-br from-orange-50 via-white to-orange-50 dark:from-gray-900 dark:via-gray-950 dark:to-gray-900">
        <!-- Navigation -->
        <nav class="border-b bg-white/80 backdrop-blur-sm dark:bg-gray-950/80 sticky top-0 z-50">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-500 text-white">
                        <Trophy class="h-5 w-5" />
                    </div>
                    <span class="text-xl font-bold text-gray-900 dark:text-white">
                        Optimum<span class="text-orange-500">Race</span>
                    </span>
                </div>

                <div class="flex items-center gap-4">
                    <template v-if="$page.props.auth.user">
                        <Link :href="dashboard()">
                            <Button variant="default" class="bg-orange-500 hover:bg-orange-600">
                                Dashboard
                            </Button>
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="login()">
                            <Button variant="ghost">Log in</Button>
                        </Link>
                        <Link :href="register()">
                            <Button variant="default" class="bg-orange-500 hover:bg-orange-600">
                                Register
                            </Button>
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative overflow-hidden px-4 py-20 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl text-center">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-6xl">
                    Registrasi<br />
                    <span class="text-orange-500">Event Otomotif</span>
                </h1>
                <p class="mx-auto mt-6 max-w-2xl text-lg text-gray-600 dark:text-gray-300">
                    Daftarkan diri Anda ke berbagai event balap: Road Race, Enduro, Motocross, dan Grasstrack.
                    Satu platform untuk semua kebutuhan registrasi event otomotif Anda.
                </p>
                <div class="mt-10 flex items-center justify-center gap-4">
                    <Link :href="register()">
                        <Button size="lg" class="bg-orange-500 hover:bg-orange-600 px-8">
                            Mulai Mendaftar
                        </Button>
                    </Link>
                    <Link href="#events">
                        <Button size="lg" variant="outline">
                            Lihat Event
                        </Button>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="border-y bg-white/50 dark:bg-gray-900/50 px-4 py-12 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                    <div class="text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-orange-100 dark:bg-orange-900/30">
                            <Trophy class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                        </div>
                        <p class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">4</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Kategori Event</p>
                    </div>
                    <div class="text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-orange-100 dark:bg-orange-900/30">
                            <Calendar class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                        </div>
                        <p class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">36+</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Event per Tahun</p>
                    </div>
                    <div class="text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-orange-100 dark:bg-orange-900/30">
                            <Users class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                        </div>
                        <p class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">102+</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Rider Terdaftar</p>
                    </div>
                    <div class="text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-orange-100 dark:bg-orange-900/30">
                            <MapPin class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                        </div>
                        <p class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">13+</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Lokasi Event</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Events Section -->
        <section id="events" class="px-4 py-20 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="mb-12 text-center">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl">
                        Event <span class="text-orange-500">Terbaru</span>
                    </h2>
                    <p class="mx-auto mt-4 max-w-2xl text-lg text-gray-600 dark:text-gray-300">
                        Jelajahi dan daftarkan diri Anda ke event balap yang sedang dibuka.
                    </p>
                </div>

                <div v-if="events.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="event in events" :key="event.id" class="group overflow-hidden transition-all hover:shadow-lg">
                        <CardHeader class="relative bg-gradient-to-br from-orange-500 to-orange-600 text-white">
                            <div class="absolute right-4 top-4">
                                <span class="inline-flex items-center rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-medium text-white backdrop-blur-sm">
                                    {{ event.discipline?.name ?? 'Event Otomotif' }}
                                </span>
                            </div>
                            <CardTitle class="text-xl">{{ event.title }}</CardTitle>
                            <CardDescription class="text-orange-100">
                                {{ event.eo_name ?? '-' }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3 pt-6">
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <MapPin class="h-4 w-4 text-orange-500" />
                                {{ event.location }}
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <Calendar class="h-4 w-4 text-orange-500" />
                                {{ formatDate(event.start_date) }}
                                <span v-if="event.end_date"> - {{ formatDate(event.end_date) }}</span>
                            </div>
                            <div class="pt-4">
                                <Link :href="`/events/${event.slug}`">
                                    <Button class="w-full bg-orange-500 hover:bg-orange-600">
                                        Lihat Detail
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div v-else class="text-center py-12">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                        <Calendar class="h-10 w-10 text-gray-400" />
                    </div>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Belum ada event</h3>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">Event akan segera ditambahkan. Silakan cek kembali nanti.</p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t bg-white dark:bg-gray-950 px-4 py-12 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col items-center justify-between gap-6 md:flex-row">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-500 text-white">
                            <Trophy class="h-4 w-4" />
                        </div>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">
                            Optimum<span class="text-orange-500">Race</span>
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        © {{ new Date().getFullYear() }} Optimum Race. Platform Event Otomotif.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

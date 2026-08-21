<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Calendar, ChevronLeft, MapPin, Trophy, Layers, Info } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import ClassEligibilityMatrix from '@/components/ClassEligibilityMatrix.vue';

interface Event {
    id: string;
    title: string;
    slug: string;
    location: string;
    start_date: string;
    end_date: string | null;
    eo_name: string;
    status: string;
    description: string | null;
    discipline_id: string;
}

interface Discipline {
    id: string;
    name: string;
    slug: string;
}

interface Status {
    value: string;
    label: string;
}

interface RaceClass {
    id: string;
    name: string;
    code?: string;
    level_order?: number;
}

interface EligiblePair {
    from: string;
    to: string;
}

interface Props {
    event: Event;
    disciplines: Discipline[];
    statuses: Status[];
    race_classes: RaceClass[];
    default_eligible_pairs: EligiblePair[];
}

const props = defineProps<Props>();

const activeTab = ref('info');

const form = useForm({
    title: props.event.title,
    discipline_id: props.event.discipline_id,
    location: props.event.location,
    start_date: props.event.start_date,
    end_date: props.event.end_date ?? '',
    eo_name: props.event.eo_name,
    status: props.event.status,
    description: props.event.description ?? '',
    eligible_pairs: props.default_eligible_pairs ?? ([] as EligiblePair[]),
});

const submit = () => {
    form.put(route('admin.events.update', props.event.id), {
        onSuccess: () => {
            toast.success('Event dan matriks eligibilitas berhasil diperbarui!', { duration: 2200 });
            form.reset('title', 'discipline_id', 'location', 'start_date', 'end_date', 'eo_name', 'status', 'description');
        },
        onError: () => {
            toast.error('Gagal memperbarui event. Silakan periksa isian Anda.');
        },
    });
};
</script>

<template>
    <Head title="Edit Event" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <Link :href="route('admin.events.index')">
                <Button variant="outline" size="icon">
                    <ChevronLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Edit Event</h1>
                <p class="text-muted-foreground">Perbarui informasi event {{ event.title }}.</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <Tabs v-model="activeTab" default-value="info" class="w-full">
                <div class="flex items-center justify-between flex-wrap gap-3 border-b border-slate-200 pb-3 dark:border-slate-800">
                    <TabsList class="bg-slate-100 dark:bg-slate-900/60 ring-1 ring-slate-200/60 dark:ring-slate-800/70">
                        <TabsTrigger value="info" class="gap-2">
                            <Info class="h-4 w-4" /> Informasi Event
                        </TabsTrigger>
                        <TabsTrigger value="eligibility" class="gap-2">
                            <Layers class="h-4 w-4 text-orange-500" /> Matriks Eligibilitas
                        </TabsTrigger>
                    </TabsList>
                    <Badge variant="outline" class="border-slate-300 text-slate-600 dark:border-slate-700 dark:text-slate-400">
                        ID: {{ event.id.slice(0, 8) }}...
                    </Badge>
                </div>

                <!-- TAB #1: INFORMASI EVENT -->
                <TabsContent value="info" class="space-y-6">
                    <!-- Basic Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Informasi Dasar</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="title">Nama Event <span class="text-red-500">*</span></Label>
                                    <Input
                                        id="title"
                                        v-model="form.title"
                                        placeholder="Contoh: BKS Racing Championship 2024"
                                        :error="form.errors.title"
                                    />
                                    <p v-if="form.errors.title" class="text-sm text-red-500">{{ form.errors.title }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="eo_name">Nama EO <span class="text-red-500">*</span></Label>
                                    <Input
                                        id="eo_name"
                                        v-model="form.eo_name"
                                        placeholder="Nama Event Organizer"
                                        :error="form.errors.eo_name"
                                    />
                                    <p v-if="form.errors.eo_name" class="text-sm text-red-500">{{ form.errors.eo_name }}</p>
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="discipline">Disiplin <span class="text-red-500">*</span></Label>
                                    <Select v-model="form.discipline_id">
                                        <SelectTrigger :class="form.errors.discipline_id && 'border-red-500'">
                                            <SelectValue placeholder="Pilih disiplin" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="discipline in disciplines"
                                                :key="discipline.id"
                                                :value="discipline.id"
                                            >
                                                {{ discipline.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.discipline_id" class="text-sm text-red-500">{{ form.errors.discipline_id }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="status">Status <span class="text-red-500">*</span></Label>
                                    <Select v-model="form.status">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih status" />
                                        </SelectTrigger>
                                        <SelectContent>
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

                    <!-- Location & Date -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Lokasi & Tanggal</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="location">Lokasi <span class="text-red-500">*</span></Label>
                                <div class="relative">
                                    <MapPin class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="location"
                                        v-model="form.location"
                                        placeholder="Alamat lengkap lokasi event"
                                        class="pl-10"
                                        :error="form.errors.location"
                                    />
                                </div>
                                <p v-if="form.errors.location" class="text-sm text-red-500">{{ form.errors.location }}</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="start_date">Tanggal Mulai <span class="text-red-500">*</span></Label>
                                    <div class="relative">
                                        <Calendar class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            id="start_date"
                                            v-model="form.start_date"
                                            type="date"
                                            class="pl-10"
                                            :error="form.errors.start_date"
                                        />
                                    </div>
                                    <p v-if="form.errors.start_date" class="text-sm text-red-500">{{ form.errors.start_date }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="end_date">Tanggal Selesai</Label>
                                    <div class="relative">
                                        <Calendar class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            id="end_date"
                                            v-model="form.end_date"
                                            type="date"
                                            class="pl-10"
                                            :error="form.errors.end_date"
                                        />
                                    </div>
                                    <p v-if="form.errors.end_date" class="text-sm text-red-500">{{ form.errors.end_date }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Description -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Deskripsi</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Textarea
                                v-model="form.description"
                                placeholder="Deskripsi lengkap tentang event..."
                                rows="4"
                                :error="form.errors.description"
                            />
                            <p v-if="form.errors.description" class="mt-2 text-sm text-red-500">{{ form.errors.description }}</p>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- TAB #2: ELIGIBILITY MATRIX -->
                <TabsContent value="eligibility">
                    <Card
                        class="border-orange-300/60 ring-1 ring-orange-500/20 shadow-lg"
                        :class="[race_classes.length === 0 && 'opacity-60']"
                    >
                        <CardHeader class="pb-3">
                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <CardTitle class="flex items-center gap-2 text-xl">
                                        <Layers class="h-5 w-5 text-orange-500" />
                                        Matriks Eligibilitas Kelas
                                        <Badge
                                            v-if="race_classes.length"
                                            class="bg-orange-500/15 text-orange-700 border-orange-500/40 dark:text-orange-300"
                                        >
                                            {{ race_classes.length }} Kelas
                                        </Badge>
                                        <Badge v-else variant="outline" class="text-amber-700 border-amber-400 dark:text-amber-400">
                                            Belum Ada Kelas
                                        </Badge>
                                    </CardTitle>
                                    <CardDescription class="text-sm">
                                        Pengaturan aturan <strong>turun kelas / lintas kelas</strong> spesifik per event.
                                        <span class="text-orange-600 dark:text-orange-400 font-semibold">
                                            Aturan ini menggantikan level_order statis di menu Race Class.
                                        </span>
                                        Tetapkan rider dari kelas sumber (baris) boleh masuk ke kelas tujuan (kolom) mana saja.
                                    </CardDescription>
                                </div>
                                <Link
                                    v-if="race_classes.length === 0"
                                    :href="route('admin.race-classes.index') + '?event=' + event.id"
                                >
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="border-orange-500/40 text-orange-600 hover:bg-orange-50 dark:text-orange-400 dark:hover:bg-orange-950/30"
                                    >
                                        <Layers class="mr-1.5 h-4 w-4" />
                                        Buat Kelas Race Dulu
                                    </Button>
                                </Link>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-2 pt-0">
                            <template v-if="race_classes && race_classes.length > 0">
                                <ClassEligibilityMatrix
                                    v-model="form.eligible_pairs"
                                    :race-classes="race_classes"
                                />
                                <p v-if="form.errors['eligible_pairs']" class="text-sm text-red-500">
                                    {{ form.errors['eligible_pairs'] }}
                                </p>
                            </template>
                            <template v-else>
                                <div
                                    class="rounded-lg border border-dashed border-orange-300/60 bg-orange-50/60 px-4 py-10 text-center dark:border-orange-800/40 dark:bg-orange-950/20"
                                >
                                    <Layers class="mx-auto mb-3 h-10 w-10 text-orange-400/60 opacity-60" />
                                    <p class="font-medium text-orange-900 dark:text-orange-200">
                                        Belum ada kelas race untuk event ini.
                                    </p>
                                    <p class="mt-1 text-sm text-orange-800/80 dark:text-orange-300/70">
                                        Tambahkan minimal 2 kelas Race terlebih dahulu di menu
                                        <code class="rounded bg-white/60 px-1.5 py-0.5 text-xs ring-1 ring-orange-300/40 dark:bg-slate-900 dark:ring-orange-800/50">
                                            Admin → Kelas Race
                                        </code>
                                        untuk memunculkan matriks eligibility.
                                    </p>
                                </div>
                            </template>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

            <!-- Submit Buttons -->
            <div class="flex items-center gap-4 pt-2">
                <Button
                    type="submit"
                    class="bg-orange-500 hover:bg-orange-600"
                    :disabled="form.processing"
                >
                    <Trophy class="mr-2 h-4 w-4" />
                    Simpan Perubahan
                </Button>
                <Link :href="route('admin.events.index')">
                    <Button variant="outline" type="button">
                        Batal
                    </Button>
                </Link>
            </div>
        </form>
    </div>
</template>

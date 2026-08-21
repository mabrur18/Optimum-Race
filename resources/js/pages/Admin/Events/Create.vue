<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Calendar, ChevronLeft, MapPin, Trophy } from 'lucide-vue-next';

interface Discipline {
    id: string;
    name: string;
    slug: string;
}

interface Status {
    value: string;
    label: string;
}

interface Props {
    disciplines: Discipline[];
    statuses: Status[];
}

const props = defineProps<Props>();

const form = useForm({
    title: '',
    discipline_id: '',
    location: '',
    start_date: '',
    end_date: '',
    eo_name: '',
    status: 'draft',
    description: '',
});

const submit = () => {
    form.post(route('admin.events.store'), {
        onSuccess: () => {
            toast.success('Event berhasil dibuat! Mengalihkan ke daftar event...', { duration: 2000 });
            form.reset();
        },
        onError: () => {
            toast.error('Gagal menyimpan event. Silakan periksa isian Anda.');
        },
    });
};
</script>

<template>
    <Head title="Buat Event" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <Link :href="route('admin.events.index')">
                <Button variant="outline" size="icon">
                    <ChevronLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Buat Event</h1>
                <p class="text-muted-foreground">Buat event balap baru untuk organizer Anda.</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
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

            <!-- Submit Buttons -->
            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    class="bg-orange-500 hover:bg-orange-600"
                    :disabled="form.processing"
                >
                    <Trophy class="mr-2 h-4 w-4" />
                    Simpan Event
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

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ChevronLeft, Layers, Save } from 'lucide-vue-next';

interface EventItem { id: string; title: string }
interface Template { id: string; name: string; level_order?: number | null }

interface Props {
    events: EventItem[];
    templates: Template[];
    selected_event_id?: string | null;
}

const props = defineProps<Props>();

const form = useForm({
    event_id: props.selected_event_id ?? '',
    template_id: '',
    name: '',
    code: '',
    level_order: 0,
    quota: '' as string | number,
    price: 0,
});

const submit = () => {
    form.post(route('admin.race-classes.store'), {
        onSuccess: () => {
            toast.success('Kelas race berhasil ditambahkan ke event!', { duration: 2000 });
            form.reset();
        },
        onError: () => {
            toast.error('Gagal menambahkan kelas race. Silakan periksa isian Anda.');
        },
    });
};
</script>

<template>
    <Head title="Buat Kelas Race" />

    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <Link :href="route('admin.race-classes.index')">
                <Button variant="outline" size="icon">
                    <ChevronLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Buat Kelas Race</h1>
                <p class="text-muted-foreground">Tambahkan kelas balap baru ke event.</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Data Dasar Kelas</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <Label for="event">Event <span class="text-red-500">*</span></Label>
                            <Select v-model="form.event_id">
                                <SelectTrigger :class="form.errors.event_id && 'border-red-500'">
                                    <SelectValue placeholder="Pilih event" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="ev in events"
                                        :key="ev.id"
                                        :value="ev.id"
                                    >
                                        {{ ev.title }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.event_id" class="text-sm text-red-500">{{ form.errors.event_id }}</p>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label for="template">Template Kelas (opsional)</Label>
                            <Select v-model="form.template_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Gunakan template kelas umum / Buat custom" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Custom (tidak pakai template)</SelectItem>
                                    <SelectItem
                                        v-for="t in templates"
                                        :key="t.id"
                                        :value="t.id"
                                    >
                                        {{ t.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <Label for="name">Nama Kelas <span class="text-red-500">*</span></Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Contoh: MX 1 Expert"
                                :error="form.errors.name"
                            />
                            <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="code">Kode Kelas <span class="text-red-500">*</span></Label>
                            <Input
                                id="code"
                                v-model="form.code"
                                placeholder="Contoh: BKS-MX1-2024"
                                :error="form.errors.code"
                            />
                            <p v-if="form.errors.code" class="text-sm text-red-500">{{ form.errors.code }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Kuota & Biaya</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="level_order">Urutan Level</Label>
                            <Input
                                id="level_order"
                                type="number"
                                min="0"
                                v-model.number="form.level_order"
                                :error="form.errors.level_order"
                            />
                            <p v-if="form.errors.level_order" class="text-sm text-red-500">{{ form.errors.level_order }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="quota">Kuota Peserta</Label>
                            <Input
                                id="quota"
                                type="number"
                                min="0"
                                v-model="form.quota"
                                placeholder="Kosongkan = tanpa batas"
                                :error="form.errors.quota"
                            />
                            <p v-if="form.errors.quota" class="text-sm text-red-500">{{ form.errors.quota }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="price">Biaya Pendaftaran (Rp) <span class="text-red-500">*</span></Label>
                            <Input
                                id="price"
                                type="number"
                                min="0"
                                step="1000"
                                v-model.number="form.price"
                                :error="form.errors.price"
                            />
                            <p v-if="form.errors.price" class="text-sm text-red-500">{{ form.errors.price }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    class="bg-orange-500 hover:bg-orange-600"
                    :disabled="form.processing"
                >
                    <Save class="mr-2 h-4 w-4" />
                    Simpan Kelas
                </Button>
                <Link :href="route('admin.race-classes.index')">
                    <Button variant="outline" type="button">
                        Batal
                    </Button>
                </Link>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ChevronLeft, Save } from 'lucide-vue-next';

interface RaceClassData {
    id: string;
    event_id: string;
    template_id?: string | null;
    name: string;
    code: string;
    level_order: number;
    quota: number | null;
    price: number;
}

interface Props {
    race_class: RaceClassData;
    events: { id: string; title: string }[];
    templates: { id: string; name: string; level_order?: number | null }[];
}

const props = defineProps<Props>();

const form = useForm({
    event_id: props.race_class.event_id ?? '',
    template_id: props.race_class.template_id ?? '',
    name: props.race_class.name,
    code: props.race_class.code,
    level_order: props.race_class.level_order ?? 0,
    quota: props.race_class.quota !== null ? props.race_class.quota : '' as string | number,
    price: props.race_class.price ?? 0,
});

const submit = () => {
    form.put(route('admin.race-classes.update', props.race_class.id), {
        onSuccess: () => {
            toast.success('Kelas race berhasil diperbarui!', { duration: 2000 });
        },
        onError: () => {
            toast.error('Gagal memperbarui kelas race. Silakan periksa isian Anda.');
        },
    });
};
</script>

<template>
    <Head :title="'Edit - ' + race_class.name" />

    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <Link :href="route('admin.race-classes.index')">
                <Button variant="outline" size="icon">
                    <ChevronLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Edit Kelas Race</h1>
                <p class="text-muted-foreground">Perbarui detail kelas {{ race_class.name }}.</p>
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
                                    <SelectValue placeholder="Custom (tidak pakai template)" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Custom</SelectItem>
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
                            <Input id="name" v-model="form.name" :error="form.errors.name" />
                            <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="code">Kode Kelas <span class="text-red-500">*</span></Label>
                            <Input id="code" v-model="form.code" :error="form.errors.code" />
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
                            <Input id="level_order" type="number" min="0" v-model.number="form.level_order" />
                        </div>
                        <div class="space-y-2">
                            <Label for="quota">Kuota Peserta</Label>
                            <Input id="quota" type="number" min="0" v-model="form.quota" placeholder="Kosongkan = tanpa batas" />
                        </div>
                        <div class="space-y-2">
                            <Label for="price">Biaya Pendaftaran (Rp) <span class="text-red-500">*</span></Label>
                            <Input id="price" type="number" min="0" step="1000" v-model.number="form.price" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="flex items-center gap-4">
                <Button type="submit" class="bg-orange-500 hover:bg-orange-600" :disabled="form.processing">
                    <Save class="mr-2 h-4 w-4" />
                    Simpan Perubahan
                </Button>
                <Link :href="route('admin.race-classes.index')">
                    <Button variant="outline" type="button">Batal</Button>
                </Link>
            </div>
        </form>
    </div>
</template>

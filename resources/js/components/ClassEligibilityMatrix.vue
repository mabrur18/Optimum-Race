<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { CheckCircle2, XCircle, Trophy, Layers3, RotateCcw, ArrowDownWideNarrow, Info } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

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

const props = defineProps<{
    raceClasses: RaceClass[];
    modelValue: EligiblePair[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: EligiblePair[]];
}>();

const isDiagonalLocked = ref(true);
const hoveredRow = ref<string | null>(null);
const hoveredCol = ref<string | null>(null);

const allowedMap = computed<Record<string, boolean>>(() => {
    const m: Record<string, boolean> = {};
    props.modelValue.forEach((p) => {
        if (p.from && p.to) m[p.from + '__' + p.to] = true;
    });
    props.raceClasses.forEach((rc) => {
        const key = rc.id + '__' + rc.id;
        if (isDiagonalLocked.value) {
            m[key] = true;
        }
    });
    return m;
});

function isAllowed(fromId: string, toId: string): boolean {
    return allowedMap.value[fromId + '__' + toId] ?? (fromId === toId && isDiagonalLocked.value);
}

function toggle(fromId: string, toId: string) {
    if (fromId === toId && isDiagonalLocked.value) return;

    const pairExists = (arr: EligiblePair[], f: string, t: string) =>
        arr.some((p) => p.from === f && p.to === t);

    const removePair = (arr: EligiblePair[], f: string, t: string) =>
        arr.filter((p) => !(p.from === f && p.to === t));

    let next = [...props.modelValue];
    if (pairExists(next, fromId, toId)) {
        next = removePair(next, fromId, toId);
    } else {
        next.push({ from: fromId, to: toId });
    }
    emit('update:modelValue', next);
}

function resetDefault() {
    const next: EligiblePair[] = [];
    props.raceClasses.forEach((rc) => {
        next.push({ from: rc.id, to: rc.id });
    });
    emit('update:modelValue', next);
}

function applyDownhillTurunKelas() {
    const next: EligiblePair[] = [];
    props.raceClasses.forEach((row, rowIdx) => {
        props.raceClasses.forEach((col, colIdx) => {
            if (colIdx >= rowIdx) {
                next.push({ from: row.id, to: col.id });
            }
        });
    });
    emit('update:modelValue', next);
}

function cellClass(fromId: string, toId: string): string {
    const diagonal = fromId === toId;
    const allowed = isAllowed(fromId, toId);
    const rowHover = hoveredRow.value === fromId;
    const colHover = hoveredCol.value === toId;

    const base =
        'relative transition-all duration-150 group border text-center p-2 align-middle select-none';

    let color = '';
    if (diagonal) {
        color = allowed
            ? 'bg-emerald-400/20 border-emerald-500/50 dark:bg-emerald-500/20 dark:border-emerald-400/50'
            : 'bg-amber-200/40 border-amber-500/50';
    } else if (allowed) {
        color =
            'bg-amber-300/25 border-amber-500/40 dark:bg-amber-500/20 dark:border-amber-400/50 hover:bg-emerald-400/30';
    } else {
        color =
            'bg-red-100/40 border-red-300/50 dark:bg-red-950/30 dark:border-red-800/40 hover:bg-red-200/60';
    }

    const highlight =
        (rowHover || colHover) ? 'ring-1 ring-orange-500/40 dark:ring-orange-400/40 z-[1]' : '';

    return [base, color, highlight].join(' ');
}

watch(
    isDiagonalLocked,
    (locked) => {
        let next = [...props.modelValue];
        props.raceClasses.forEach((rc) => {
            const found = next.some((p) => p.from === rc.id && p.to === rc.id);
            if (locked && !found) {
                next.push({ from: rc.id, to: rc.id });
            } else if (!locked && found) {
                next = next.filter((p) => !(p.from === rc.id && p.to === rc.id));
            }
        });
        emit('update:modelValue', next);
    },
    { immediate: false },
);

const classesCount = computed(() => props.raceClasses.length);
const allowedCount = computed(() => {
    let c = 0;
    props.raceClasses.forEach((r) => {
        props.raceClasses.forEach((t) => {
            if (isAllowed(r.id, t.id)) c++;
        });
    });
    return c;
});
const coveragePercent = computed(() => {
    const total = classesCount.value * classesCount.value;
    if (!total) return 0;
    return Math.round((allowedCount.value / total) * 100);
});
</script>

<template>
    <TooltipProvider delayDuration="150">
        <div
            class="rounded-xl border border-orange-200/70 bg-gradient-to-br from-slate-900 via-slate-900 to-orange-950/30 text-white shadow-xl overflow-hidden dark:border-orange-900/40"
        >
            <!-- Matrix Header / Toolbar -->
            <div
                class="flex flex-col gap-4 border-b border-orange-500/30 bg-gradient-to-r from-slate-900 to-orange-900/40 p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2 text-lg font-bold">
                        <Trophy class="h-5 w-5 text-orange-400" />
                        <span>Matriks Eligibilitas Kelas</span>
                        <Badge
                            class="bg-orange-500/20 text-orange-200 border-orange-500/40 font-medium tracking-wide"
                        >
                            {{ classesCount }} Kelas
                        </Badge>
                    </div>
                    <p class="text-xs text-slate-300 sm:text-sm">
                        Tentukan rider dari <strong class="text-orange-300">Kelas Sumber (Baris)</strong>
                        boleh ikut ke <strong class="text-orange-300">Kelas Tujuan (Kolom)</strong> mana saja.
                        Hijau diagonal = kelas utama (selalu diizinkan), kuning = boleh lintas/naik-turun,
                        merah = diblokir.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Badge
                                class="cursor-pointer border border-emerald-500/50 bg-emerald-500/15 text-emerald-200"
                                :class="[isDiagonalLocked && 'ring-1 ring-emerald-400/60']"
                                @click="isDiagonalLocked = !isDiagonalLocked"
                            >
                                <Layers3 class="mr-1 h-3 w-3" />
                                Diagonal {{ isDiagonalLocked ? 'TERKUNCI (Selalu Ya)' : 'Bisa Ubah' }}
                            </Badge>
                        </TooltipTrigger>
                        <TooltipContent side="bottom" class="max-w-xs text-xs">
                            Default ON: Baris = Kolom (diri sendiri) selalu ✔️. Matikan jika ingin blokir
                            kelas utama juga (tidak disarankan untuk race normal).
                        </TooltipContent>
                    </Tooltip>

                    <Button
                        type="button"
                        size="sm"
                        variant="secondary"
                        class="bg-amber-500/20 text-amber-100 border border-amber-500/40 hover:bg-amber-500/30 hover:text-amber-50"
                        @click="applyDownhillTurunKelas"
                    >
                        <ArrowDownWideNarrow class="mr-1.5 h-3.5 w-3.5" />
                        Auto Turun Kelas
                    </Button>

                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        class="border-white/20 text-white/80 hover:bg-white/10 hover:text-white"
                        @click="resetDefault"
                    >
                        <RotateCcw class="mr-1.5 h-3.5 w-3.5" />
                        Reset Hanya Diagonal
                    </Button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div
                class="grid grid-cols-1 gap-3 border-b border-white/5 bg-slate-900/40 px-4 py-3 sm:grid-cols-3"
            >
                <div class="flex items-center gap-3 text-sm">
                    <Info class="h-4 w-4 shrink-0 text-orange-400" />
                    <div class="space-y-0.5">
                        <div class="text-xs uppercase tracking-wide text-slate-400">
                            Coverage Eligibilitas
                        </div>
                        <div class="flex items-center gap-2 font-bold text-white">
                            <div class="h-2 w-24 overflow-hidden rounded-full bg-white/10">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-emerald-400 via-amber-400 to-orange-500 transition-all"
                                    :style="{ width: coveragePercent + '%' }"
                                ></div>
                            </div>
                            <span class="text-sm">{{ coveragePercent }}%</span>
                            <span class="text-xs text-slate-400">
                                ({{ allowedCount }}/{{ classesCount * classesCount }} slot)
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-300 sm:col-span-2 sm:justify-end">
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-500/15 px-2 py-1 ring-1 ring-emerald-500/40">
                        <span class="h-2.5 w-2.5 rounded-sm bg-emerald-400/60 ring-1 ring-emerald-400"></span>
                        Diagonal / Kelas Utama
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-500/15 px-2 py-1 ring-1 ring-amber-500/40">
                        <span class="h-2.5 w-2.5 rounded-sm bg-amber-400/70 ring-1 ring-amber-400"></span>
                        Boleh Lintas / Turun
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-red-500/15 px-2 py-1 ring-1 ring-red-500/40">
                        <span class="h-2.5 w-2.5 rounded-sm bg-red-400/60 ring-1 ring-red-400"></span>
                        Diblokir
                    </span>
                </div>
            </div>

            <!-- Scrollable Matrix Table -->
            <div class="relative max-h-[560px] overflow-auto bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white">
                <table
                    class="border-separate border-spacing-0 text-sm"
                    :style="{ minWidth: (raceClasses.length + 1) * 150 + 'px' }"
                >
                    <!-- HEADER KOLOM (Sticky Top) -->
                    <thead>
                        <tr>
                            <th
                                class="sticky top-0 left-0 z-30 w-64 min-w-64 bg-slate-900 text-white text-xs uppercase tracking-wider font-semibold border-b border-r border-white/10 p-3 text-left"
                            >
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-[10px] font-normal text-slate-400">
                                        Kelas Sumber (Rider) ▾
                                    </span>
                                    <span class="text-orange-300">
                                        Baris ↓ / Tujuan ▸
                                    </span>
                                </div>
                            </th>
                            <th
                                v-for="col in raceClasses"
                                :key="'head-' + col.id"
                                class="sticky top-0 z-20 min-w-[140px] bg-gradient-to-b from-slate-900 to-slate-800 text-white border-b border-r border-white/10 p-2"
                                :class="[
                                    hoveredCol === col.id && 'bg-slate-700 ring-1 ring-inset ring-orange-400/50',
                                ]"
                                @mouseenter="hoveredCol = col.id"
                                @mouseleave="hoveredCol = null"
                            >
                                <div class="flex flex-col items-center gap-1">
                                    <span
                                        class="rounded bg-orange-500/20 px-1.5 py-0.5 text-[10px] font-mono text-orange-200 ring-1 ring-orange-500/40"
                                    >
                                        {{ col.code?.toUpperCase()?.slice(0, 8) || ('LV.' + (col.level_order ?? '?')) }}
                                    </span>
                                    <span
                                        class="text-center text-[12px] font-bold leading-tight"
                                        :title="col.name"
                                    >
                                        {{ col.name }}
                                    </span>
                                </div>
                            </th>
                        </tr>
                    </thead>

                    <!-- BODY -->
                    <tbody>
                        <tr
                            v-for="(row, rowIdx) in raceClasses"
                            :key="'row-' + row.id"
                            :class="[hoveredRow === row.id && 'bg-orange-50/60 dark:bg-orange-950/30']"
                            @mouseenter="hoveredRow = row.id"
                            @mouseleave="hoveredRow = null"
                        >
                            <!-- STICKY FIRST COLUMN (Baris / Kelas Sumber) -->
                            <td
                                class="sticky left-0 z-10 w-64 min-w-64 border-b border-r border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 p-3"
                                :class="[
                                    (rowIdx % 2 === 0) ? '' : 'bg-slate-50 dark:bg-slate-800/70',
                                    hoveredRow === row.id && 'bg-orange-100 dark:bg-orange-950/50',
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-xs font-black text-white shadow-md"
                                        :class="[
                                            (row.id === raceClasses[0]?.id)
                                                ? 'bg-gradient-to-br from-rose-500 to-pink-600'
                                                : ((rowIdx === raceClasses.length - 1)
                                                    ? 'bg-gradient-to-br from-emerald-500 to-teal-600'
                                                    : 'bg-gradient-to-br from-indigo-500 to-violet-600'),
                                        ]"
                                    >
                                        {{ colSpan(row, rowIdx) }}
                                    </div>
                                    <div class="flex min-w-0 flex-col">
                                        <span
                                            class="truncate text-[13px] font-bold text-slate-900 dark:text-white"
                                            :title="row.name"
                                        >
                                            {{ row.name }}
                                        </span>
                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                                            <span class="font-mono">{{ row.code?.toUpperCase() }}</span>
                                            <span>·</span>
                                            <span>Level #{{ row.level_order ?? rowIdx + 1 }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- SEL DATA MATRIX -->
                            <td
                                v-for="col in raceClasses"
                                :key="'cell-' + row.id + '-' + col.id"
                                :class="cellClass(row.id, col.id)"
                                @click="toggle(row.id, col.id)"
                            >
                                <button
                                    type="button"
                                    class="flex h-full w-full items-center justify-center py-1 focus:outline-none"
                                    :disabled="row.id === col.id && isDiagonalLocked"
                                    :title="
                                        (row.id === col.id && isDiagonalLocked)
                                            ? 'Diagonal terkunci: rider selalu boleh masuk ke kelas utamanya'
                                            : (isAllowed(row.id, col.id)
                                                ? 'Klik untuk BLOKIR: ' + row.name + ' → ' + col.name
                                                : 'Klik untuk IZINKAN: ' + row.name + ' → ' + col.name)
                                    "
                                >
                                    <CheckCircle2
                                        v-if="isAllowed(row.id, col.id)"
                                        class="h-6 w-6 transition-all group-hover:scale-110"
                                        :class="
                                            row.id === col.id
                                                ? 'text-emerald-700 dark:text-emerald-300 drop-shadow-sm'
                                                : 'text-emerald-600/90 dark:text-emerald-400'
                                        "
                                    />
                                    <XCircle
                                        v-else
                                        class="h-6 w-6 text-red-600/80 transition-all group-hover:scale-110 dark:text-red-400"
                                    />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer Hint -->
            <div class="border-t border-white/5 bg-slate-900/60 px-4 py-2 text-[11px] text-slate-400">
                💡 <strong class="text-slate-200">Tips:</strong> hover di nama kelas → cross highlight
                seluruh baris + kolom. Klik sel matriks untuk toggle ✔/✗. Gunakan
                <span class="text-amber-300">"Auto Turun Kelas"</span> untuk setting cepat: level tinggi
                otomatis boleh masuk ke level dibawahnya.
            </div>
        </div>
    </TooltipProvider>
</template>

<script lang="ts">
function colSpan(rc: { level_order?: number; code?: string }, idx: number) {
    if (rc.level_order !== undefined && rc.level_order !== null) return String(rc.level_order);
    if (rc.code) return rc.code.slice(0, 2).toUpperCase();
    return String(idx + 1);
}
</script>

<template>
    <div
        class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
        <div class="size-12 rounded-xl flex items-center justify-center" :class="colorClasses">
            <span class="material-symbols-outlined">{{ icon }}</span>
        </div>
        <div class="flex-1">
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">{{ label }}</p>
            <div class="flex items-end gap-2">
                <p class="text-2xl font-bold">{{ value }}</p>
                <span v-if="unit" class="text-sm font-medium text-slate-400 mb-0.5">{{ unit }}</span>
            </div>
        </div>
        <span v-if="trend" class="text-xs font-bold" :class="trendPositive ? 'text-green-500' : 'text-red-400'">
            {{ trend }}
        </span>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    unit: { type: String, default: '' },
    icon: { type: String, required: true },
    color: { type: String, default: 'blue' },
    trend: { type: String, default: '' },
    trendPositive: { type: Boolean, default: true }
});

const colorClasses = computed(() => {
    const map = {
        blue: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600',
        emerald: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600',
        amber: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600',
        purple: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600',
        red: 'bg-red-100 dark:bg-red-900/30 text-red-600',
        orange: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600',
    };
    return map[props.color] || map.blue;
});
</script>
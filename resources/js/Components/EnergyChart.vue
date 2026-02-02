<template>
    <div
        class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-sm p-6 rounded-2xl border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-bold text-lg dark:text-white">Enerji Tüketimi</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Toplam güç kullanımı</p>
            </div>
            <div class="flex gap-2">
                <button v-for="tab in tabs" :key="tab" @click="activeTab = tab"
                    class="px-3 py-1 rounded-lg text-xs font-bold transition-colors" :class="activeTab === tab
                        ? 'bg-primary/10 text-primary'
                        : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'">
                    {{ tab }}
                </button>
            </div>
        </div>

        <!-- Bar Chart -->
        <div class="h-48 w-full flex items-end gap-1.5 pt-4">
            <div v-for="(bar, i) in bars" :key="i"
                class="flex-1 rounded-t-lg transition-all duration-300 cursor-pointer hover:opacity-80 relative group"
                :class="bar.highlight
                    ? 'bg-primary shadow-[0_0_15px_rgba(60,131,246,0.3)]'
                    : 'bg-primary/20 hover:bg-primary/40'" :style="{ height: bar.height + '%' }">
                <!-- Tooltip -->
                <div
                    class="absolute -top-8 left-1/2 -translate-x-1/2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[10px] font-bold px-2 py-1 rounded-md opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                    {{ bar.value }} kWh
                </div>
            </div>
        </div>

        <!-- X Axis Labels -->
        <div class="flex justify-between mt-2 px-1">
            <span v-for="(label, i) in xLabels" :key="i" class="text-[10px] text-slate-400 font-medium">
                {{ label }}
            </span>
        </div>

        <!-- Summary -->
        <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-200 dark:border-slate-800">
            <div>
                <span class="text-xs text-slate-500">Bugün Toplam</span>
                <p class="text-lg font-bold dark:text-white">14.2 <span
                        class="text-sm font-medium text-slate-400">kWh</span></p>
            </div>
            <div class="flex items-center gap-1 text-xs font-bold text-red-400">
                <span class="material-symbols-outlined text-sm">trending_down</span>
                -2.1% dünden
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const activeTab = ref('Canlı');
const tabs = ['Canlı', '24s', '7g', '30g'];

const bars = [
    { height: 40, value: '1.2', highlight: false },
    { height: 65, value: '1.8', highlight: false },
    { height: 55, value: '1.5', highlight: false },
    { height: 85, value: '2.4', highlight: true },
    { height: 45, value: '1.3', highlight: false },
    { height: 70, value: '2.0', highlight: false },
    { height: 95, value: '2.7', highlight: true },
    { height: 30, value: '0.9', highlight: false },
    { height: 50, value: '1.4', highlight: false },
    { height: 60, value: '1.7', highlight: false },
    { height: 40, value: '1.2', highlight: false },
    { height: 75, value: '2.1', highlight: false },
];

const xLabels = ['00', '02', '04', '06', '08', '10', '12', '14', '16', '18', '20', '22'];
</script>
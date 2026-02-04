<template>
    <div class="group relative overflow-hidden p-6 rounded-[2rem] border transition-all" :class="isOn
        ? 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md hover:shadow-xl'
        : 'bg-slate-100/50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-800 opacity-80'">

        <!-- Üst: İkon + Toggle -->
        <div class="flex justify-between items-start relative z-10">
            <button @click="togglePower"
                class="size-12 rounded-2xl flex items-center justify-center transition-all"
                :class="isOn 
                    ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-500 shadow-[0_0_20px_rgba(59,130,246,0.3)]' 
                    : 'bg-slate-200 dark:bg-slate-700 text-slate-400'">
                <span class="material-symbols-outlined text-3xl" :class="isOn ? 'filled-icon' : ''">
                    {{ isOn ? 'power' : 'power_off' }}
                </span>
            </button>
        </div>

        <!-- İsim + Tip Badge -->
        <div class="mt-6">
            <div class="flex items-center gap-2">
                <h3 class="font-bold text-lg" :class="isOn ? '' : 'text-slate-600 dark:text-slate-400'">
                    {{ name }}
                </h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-600">
                    PRİZ
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ location }}</p>
        </div>

        <!-- Enerji Tüketimi -->
        <div class="mt-4 p-3 rounded-xl" :class="isOn ? 'bg-slate-50 dark:bg-slate-800' : 'bg-slate-100/50 dark:bg-slate-800/50'">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-500" :class="isOn ? '' : 'opacity-30'">bolt</span>
                    <div>
                        <p class="text-[10px] text-slate-400 uppercase">Anlık Güç</p>
                        <p class="text-lg font-bold" :class="isOn ? 'text-slate-800 dark:text-white' : 'text-slate-400'">
                            {{ isOn ? currentPower : '0' }} <span class="text-xs font-medium">W</span>
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-slate-400 uppercase">Bugün</p>
                    <p class="text-lg font-bold" :class="isOn ? 'text-slate-800 dark:text-white' : 'text-slate-400'">
                        {{ todayUsage }} <span class="text-xs font-medium">kWh</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Mini Grafik -->
        <div class="mt-3 flex items-end gap-0.5 h-8" :class="isOn ? '' : 'opacity-30'">
            <div v-for="(bar, i) in usageHistory" :key="i" 
                class="flex-1 rounded-t transition-all"
                :class="isOn ? 'bg-blue-400/60' : 'bg-slate-300'"
                :style="{ height: bar + '%' }">
            </div>
        </div>
        <div class="flex justify-between text-[10px] text-slate-400 mt-1">
            <span>6 saat önce</span>
            <span>Şimdi</span>
        </div>

        <!-- Bağlı Cihaz -->
        <div class="mt-4 flex items-center gap-2 text-xs text-slate-500">
            <span class="material-symbols-outlined text-sm">devices</span>
            <span>{{ connectedDevice || 'Bağlı cihaz yok' }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    name: { type: String, default: 'Akıllı Priz' },
    location: { type: String, default: 'Salon' },
    initialState: { type: Boolean, default: true },
    connectedDevice: { type: String, default: 'TV + Soundbar' },
    initialPower: { type: Number, default: 85 }
});

const emit = defineEmits(['command']);

const isOn = ref(props.initialState);
const currentPower = ref(props.initialPower);
const todayUsage = ref('1.2');
const usageHistory = ref([30, 45, 60, 40, 75, 55, 80, 65, 90, 70, 85, 95]);

const togglePower = () => {
    isOn.value = !isOn.value;
    if (!isOn.value) currentPower.value = 0;
    else currentPower.value = props.initialPower;
    emit('command', { type: 'power', value: isOn.value });
};
</script>
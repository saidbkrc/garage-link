<template>
    <div class="group relative overflow-hidden p-6 rounded-[2rem] border transition-all" :class="isOn
        ? 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md hover:shadow-xl'
        : 'bg-slate-100/50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-800 opacity-80'">

        <!-- Üst: İkon + Toggle -->
        <div class="flex justify-between items-start relative z-10">
            <button @click="togglePower"
                class="size-12 rounded-2xl flex items-center justify-center transition-all"
                :class="isOn ? 'shadow-[0_0_20px_var(--glow-color)]' : 'bg-slate-200 dark:bg-slate-700 text-slate-400'"
                :style="isOn ? {
                    backgroundColor: currentTempColor + '33',
                    color: currentTempColor,
                    '--glow-color': currentTempColor + '4D'
                } : {}">
                <span class="material-symbols-outlined text-3xl" :class="isOn ? 'filled-icon' : ''">
                    {{ isOn ? 'lightbulb' : 'lightbulb_outline' }}
                </span>
            </button>
        </div>

        <!-- Sıcaklık Göstergesi - Sağ Üst -->
        <div v-if="isOn" class="absolute top-4 right-4 z-20 px-3 py-1.5 rounded-full text-xs font-bold shadow-lg"
            :style="{ backgroundColor: currentTempColor, color: temperature > 50 ? '#1e293b' : '#fff' }">
            {{ temperatureLabel }}
        </div>

        <!-- İsim + Tip Badge -->
        <div class="mt-6">
            <div class="flex items-center gap-2">
                <h3 class="font-bold text-lg" :class="isOn ? '' : 'text-slate-600 dark:text-slate-400'">
                    {{ name }}
                </h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-900/30 text-amber-600">
                    AMPUL
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ location }}</p>
        </div>

        <!-- Parlaklık -->
        <div class="mt-4 space-y-2" :class="isOn ? '' : 'opacity-30'">
            <div class="flex justify-between text-xs font-bold text-slate-400">
                <span>Parlaklık</span>
                <span :style="isOn ? { color: currentTempColor } : {}">{{ brightness }}%</span>
            </div>
            <input type="range" min="0" max="100" v-model="brightness" @change="changeBrightness" :disabled="!isOn"
                class="w-full h-1.5 rounded-full appearance-none cursor-pointer"
                :style="brightnessSliderStyle" />
        </div>

        <!-- Renk Sıcaklığı (Sıcak - Soğuk) -->
        <div class="mt-4 space-y-2" :class="isOn ? '' : 'opacity-30'">
            <div class="flex justify-between text-xs font-bold text-slate-400">
                <span class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm text-orange-400">local_fire_department</span>
                    Sıcak
                </span>
                <span class="flex items-center gap-1">
                    Soğuk
                    <span class="material-symbols-outlined text-sm text-blue-400">ac_unit</span>
                </span>
            </div>
            <div class="relative">
                <div class="absolute inset-0 h-1.5 rounded-full mt-2"
                    style="background: linear-gradient(to right, #FFA726, #FFE0B2, #FFFFFF, #E3F2FD, #64B5F6);"></div>
                <input type="range" min="0" max="100" v-model="temperature" @change="changeTemperature" :disabled="!isOn"
                    class="relative w-full h-1.5 rounded-full appearance-none cursor-pointer bg-transparent z-10"
                    :style="tempSliderStyle" />
            </div>
            <div class="flex justify-between text-[10px] text-slate-400">
                <span>2700K</span>
                <span>6500K</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    name: { type: String, default: 'Akıllı Ampul' },
    location: { type: String, default: 'Yatak Odası' },
    initialState: { type: Boolean, default: true },
    initialBrightness: { type: Number, default: 80 },
    initialTemperature: { type: Number, default: 30 }
});

const emit = defineEmits(['command']);

const isOn = ref(props.initialState);
const brightness = ref(props.initialBrightness);
const temperature = ref(props.initialTemperature); // 0 = sıcak (2700K), 100 = soğuk (6500K)

const currentTempColor = computed(() => {
    const t = temperature.value;
    if (t < 20) return '#FFA726'; // Sıcak turuncu
    if (t < 40) return '#FFE0B2'; // Sıcak beyaz
    if (t < 60) return '#FFFDE7'; // Nötr beyaz
    if (t < 80) return '#E3F2FD'; // Soğuk beyaz
    return '#64B5F6'; // Soğuk mavi
});

const temperatureLabel = computed(() => {
    const t = temperature.value;
    if (t < 25) return 'Sıcak';
    if (t < 50) return 'Doğal';
    if (t < 75) return 'Gün Işığı';
    return 'Soğuk';
});

const brightnessSliderStyle = computed(() => {
    if (!isOn.value) return { background: '#e2e8f0' };
    const percent = brightness.value;
    return {
        background: `linear-gradient(to right, ${currentTempColor.value} 0%, ${currentTempColor.value} ${percent}%, #e2e8f0 ${percent}%, #e2e8f0 100%)`,
        '--thumb-color': currentTempColor.value
    };
});

const tempSliderStyle = computed(() => {
    return {
        '--thumb-color': currentTempColor.value
    };
});

const togglePower = () => {
    isOn.value = !isOn.value;
    emit('command', { type: 'power', value: isOn.value });
};

const changeBrightness = () => {
    emit('command', { type: 'brightness', value: brightness.value });
};

const changeTemperature = () => {
    emit('command', { type: 'temperature', value: temperature.value });
};
</script>
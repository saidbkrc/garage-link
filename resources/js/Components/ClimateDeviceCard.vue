<template>
    <div class="group relative overflow-hidden p-6 rounded-[2rem] border transition-all" :class="isActive
        ? 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md hover:shadow-xl'
        : 'bg-slate-100/50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-800 opacity-80'">

        <!-- Üst: İkon + Toggle -->
        <div class="flex justify-between items-start relative z-10">
            <button @click="togglePower"
                class="size-12 rounded-2xl flex items-center justify-center transition-all"
                :class="isActive 
                    ? modeColors[currentMode].iconBg
                    : 'bg-slate-200 dark:bg-slate-700 text-slate-400'">
                <span class="material-symbols-outlined text-3xl">{{ icon }}</span>
            </button>
        </div>

        <!-- Mod Badge - Sağ Üst -->
        <div v-if="isActive" class="absolute top-4 right-16 px-2 py-1 rounded-full text-[10px] font-bold"
            :class="modeColors[currentMode].badge">
            {{ modeLabels[currentMode] }}
        </div>

        <!-- İsim + Konum -->
        <div class="mt-6">
            <div class="flex items-center gap-2">
                <h3 class="font-bold text-lg" :class="isActive ? '' : 'text-slate-600 dark:text-slate-400'">
                    {{ name }}
                </h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600">
                    KLİMA
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ location }}</p>
        </div>

        <!-- Sıcaklık Gösterimi -->
        <div class="mt-4 flex items-center justify-between" :class="isActive ? '' : 'opacity-40'">
            <div class="text-center">
                <p class="text-[10px] text-slate-400 uppercase font-bold">Mevcut</p>
                <p class="text-3xl font-bold" :class="isActive ? 'text-slate-800 dark:text-white' : 'text-slate-400'">
                    {{ currentTemp }}°
                </p>
            </div>
            <div class="flex items-center gap-1 text-slate-300 dark:text-slate-600">
                <span class="material-symbols-outlined">arrow_forward</span>
            </div>
            <div class="text-center">
                <p class="text-[10px] text-slate-400 uppercase font-bold">Hedef</p>
                <p class="text-3xl font-bold" :class="isActive ? modeColors[currentMode].text : 'text-slate-400'">
                    {{ target }}°
                </p>
            </div>
        </div>

        <!-- Hedef Sıcaklık Ayarı -->
        <div class="mt-4 flex items-center justify-center gap-3" :class="isActive ? '' : 'opacity-40'">
            <button @click="decreaseTemp" :disabled="!isActive"
                class="size-10 rounded-xl flex items-center justify-center transition-all"
                :class="isActive 
                    ? 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300' 
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-400'">
                <span class="material-symbols-outlined">remove</span>
            </button>
            <div class="w-20 text-center">
                <span class="text-2xl font-bold" :class="isActive ? modeColors[currentMode].text : 'text-slate-400'">
                    {{ target }}°C
                </span>
            </div>
            <button @click="increaseTemp" :disabled="!isActive"
                class="size-10 rounded-xl flex items-center justify-center transition-all"
                :class="isActive 
                    ? 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300' 
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-400'">
                <span class="material-symbols-outlined">add</span>
            </button>
        </div>

        <!-- Mod Seçimi -->
        <div class="mt-4 flex gap-2" :class="isActive ? '' : 'opacity-40'">
            <button v-for="(label, key) in modeLabels" :key="key"
                @click="setMode(key)" :disabled="!isActive"
                class="flex-1 py-2 rounded-xl text-xs font-bold transition-all flex flex-col items-center gap-1"
                :class="currentMode === key && isActive
                    ? modeColors[key].button
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700'">
                <span class="material-symbols-outlined text-lg">{{ modeIcons[key] }}</span>
                {{ label }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    name: { type: String, default: 'Klima' },
    location: { type: String, default: 'Salon' },
    icon: { type: String, default: 'ac_unit' },
    currentTemp: { type: Number, default: 26 },
    targetTemp: { type: Number, default: 22 },
    mode: { type: String, default: 'cool' },
    isOn: { type: Boolean, default: true }
});

const emit = defineEmits(['command']);

const isActive = ref(props.isOn);
const target = ref(props.targetTemp);
const currentMode = ref(props.mode);

const modeLabels = {
    cool: 'Soğutma',
    heat: 'Isıtma',
    auto: 'Otomatik',
    fan: 'Fan'
};

const modeIcons = {
    cool: 'ac_unit',
    heat: 'local_fire_department',
    auto: 'autorenew',
    fan: 'mode_fan'
};

const modeColors = {
    cool: {
        iconBg: 'bg-cyan-100 dark:bg-cyan-900/40 text-cyan-500 shadow-[0_0_20px_rgba(34,211,238,0.3)]',
        toggle: 'bg-cyan-500',
        text: 'text-cyan-500',
        badge: 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600',
        button: 'bg-cyan-500 text-white'
    },
    heat: {
        iconBg: 'bg-orange-100 dark:bg-orange-900/40 text-orange-500 shadow-[0_0_20px_rgba(251,146,60,0.3)]',
        toggle: 'bg-orange-500',
        text: 'text-orange-500',
        badge: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600',
        button: 'bg-orange-500 text-white'
    },
    auto: {
        iconBg: 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-500 shadow-[0_0_20px_rgba(52,211,153,0.3)]',
        toggle: 'bg-emerald-500',
        text: 'text-emerald-500',
        badge: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600',
        button: 'bg-emerald-500 text-white'
    },
    fan: {
        iconBg: 'bg-slate-200 dark:bg-slate-700 text-slate-500',
        toggle: 'bg-slate-500',
        text: 'text-slate-500',
        badge: 'bg-slate-200 dark:bg-slate-700 text-slate-600',
        button: 'bg-slate-500 text-white'
    }
};

const togglePower = () => {
    isActive.value = !isActive.value;
    emit('command', { type: 'power', value: isActive.value });
};

const increaseTemp = () => {
    if (target.value < 30) {
        target.value++;
        emit('command', { type: 'temperature', value: target.value });
    }
};

const decreaseTemp = () => {
    if (target.value > 16) {
        target.value--;
        emit('command', { type: 'temperature', value: target.value });
    }
};

const setMode = (mode) => {
    currentMode.value = mode;
    emit('command', { type: 'mode', value: mode });
};
</script>
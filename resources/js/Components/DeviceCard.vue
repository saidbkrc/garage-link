<template>
    <div class="group relative overflow-hidden p-6 rounded-[2rem] border transition-all" :class="isOn
        ? 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md hover:shadow-xl'
        : 'bg-slate-100/50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-800 opacity-80'">

        <!-- Renk Seçici Popup -->
        <div v-if="showColorPicker" ref="colorPickerRef"
            class="absolute top-16 right-2 z-30 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-4 border border-slate-200 dark:border-slate-700 w-48">
            <div class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-3">Renk Seç</div>
            <div class="grid grid-cols-5 gap-2 mb-3">
                <button v-for="color in presetColors" :key="color"
                    class="size-7 rounded-full border-2 transition-all hover:scale-110"
                    :class="selectedColor === color ? 'border-slate-900 dark:border-white scale-110 ring-2 ring-offset-1 ring-primary' : 'border-transparent'"
                    :style="{ background: color }" @click="changeColor(color)"></button>
            </div>
            <input type="color" v-model="customColor" @input="changeColor(customColor)"
                class="w-full h-8 rounded-lg cursor-pointer border-0" />
        </div>

        <!-- Üst: İkon + Toggle -->
        <div class="flex justify-between items-start relative z-10">
            <button @click="togglePower"
                class="size-12 rounded-2xl flex items-center justify-center transition-all"
                :class="isOn
                    ? 'shadow-[0_0_20px_var(--glow-color)]'
                    : 'bg-slate-200 dark:bg-slate-700 text-slate-400'"
                :style="isOn ? {
                    backgroundColor: selectedColor + '33',
                    color: selectedColor,
                    '--glow-color': selectedColor + '4D'
                } : {}">
                <span class="material-symbols-outlined text-3xl" :class="isOn ? 'filled-icon' : ''">
                    {{ isOn ? 'lightbulb' : 'lightbulb_outline' }}
                </span>
            </button>
        </div>

        <!-- Renk Seçici Butonu - Sağ Üst Köşe -->
        <button v-if="isOn" @click.stop="showColorPicker = !showColorPicker"
            class="absolute top-4 right-4 z-20 size-10 rounded-full border-2 border-white dark:border-slate-800 shadow-lg transition-all hover:scale-110 hover:shadow-xl flex items-center justify-center"
            :style="{ background: `linear-gradient(135deg, ${selectedColor}cc, ${selectedColor})` }"
            title="Renk Seç">
            <span class="material-symbols-outlined text-white text-lg drop-shadow">palette</span>
        </button>

        <!-- İsim + Durum -->
        <div class="mt-8">
            <h3 class="font-bold text-lg" :class="isOn ? '' : 'text-slate-600 dark:text-slate-400'">
                {{ device.name }}
            </h3>
            <template v-if="isOn">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ device.device_type?.name || 'LED Işık' }} · {{ device.location || 'Ana Oda' }}
                </p>
            </template>
            <template v-else>
                <div
                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-800 text-[10px] font-bold text-slate-500 mt-1">
                    <span class="size-1.5 rounded-full" :class="device.is_online ? 'bg-green-500' : 'bg-slate-400'"></span>
                    {{ device.is_online ? 'ONLINE' : 'OFFLINE' }}
                </div>
            </template>
        </div>

        <!-- Parlaklık -->
        <div class="mt-6 space-y-3" :class="isOn ? '' : 'opacity-30'">
            <div class="flex justify-between text-xs font-bold text-slate-400">
                <span>Parlaklık</span>
                <span :style="isOn ? { color: selectedColor } : {}">{{ brightness }}%</span>
            </div>
            <div class="relative">
                <input type="range" min="0" max="100" v-model="brightness" @input="updateSliderBackground" @change="changeBrightness" :disabled="!isOn"
                    ref="sliderRef"
                    class="w-full h-1.5 rounded-full appearance-none cursor-pointer bg-slate-200 dark:bg-slate-700"
                    :style="sliderStyle" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';

const props = defineProps({
    device: { type: Object, required: true },
    state: { type: Object, default: () => ({}) }
});

const emit = defineEmits(['command']);

const isOn = ref(props.state?.power === true || props.state?.power === 'on');
const brightness = ref(props.state?.brightness || 75);
const selectedColor = ref('#67E8F9');
const customColor = ref('#67E8F9');
const showColorPicker = ref(false);
const colorPickerRef = ref(null);
const sliderRef = ref(null);

const presetColors = [
    '#67E8F9', '#FB923C', '#F87171', '#4ADE80', '#A855F7',
    '#FACC15', '#60A5FA', '#F472B6', '#69888c', '#2DD4BF'
];

// Click outside ile kapat
const handleClickOutside = (e) => {
    if (showColorPicker.value && colorPickerRef.value && !colorPickerRef.value.contains(e.target)) {
        showColorPicker.value = false;
    }
};

const sliderStyle = computed(() => {
    if (!isOn.value) return {};
    
    const percent = brightness.value;
    return {
        background: `linear-gradient(to right, ${selectedColor.value} 0%, ${selectedColor.value} ${percent}%, #e2e8f0 ${percent}%, #e2e8f0 100%)`,
        '--thumb-color': selectedColor.value
    };
});

const updateSliderBackground = () => {
    // reactive güncelleme için
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});

const togglePower = () => {
    isOn.value = !isOn.value;
    showColorPicker.value = false;
    emit('command', {
        deviceId: props.device.id,
        slug: isOn.value ? 'turn_on' : 'turn_off',
        params: {}
    });
};

const changeBrightness = () => {
    emit('command', {
        deviceId: props.device.id,
        slug: 'brightness',
        params: { brightness: parseInt(brightness.value) }
    });
};

const changeColor = (color) => {
    selectedColor.value = color;
    const hex = color.replace('#', '');
    const r = parseInt(hex.slice(0, 2), 16);
    const g = parseInt(hex.slice(2, 4), 16);
    const b = parseInt(hex.slice(4, 6), 16);
    emit('command', {
        deviceId: props.device.id,
        slug: 'color',
        params: { color: `rgb(${r}, ${g}, ${b})` }
    });
};
</script>
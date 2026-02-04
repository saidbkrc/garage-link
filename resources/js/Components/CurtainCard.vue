<template>
    <div class="group relative overflow-hidden p-6 rounded-[2rem] border transition-all"
        :class="'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md hover:shadow-xl'">

        <!-- Üst: İkon + Durum -->
        <div class="flex justify-between items-start relative z-10">
            <div class="size-12 rounded-2xl flex items-center justify-center transition-all"
                :class="isMoving 
                    ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-500 animate-pulse' 
                    : 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-500'">
                <span class="material-symbols-outlined text-3xl">
                    {{ position === 0 ? 'blinds_closed' : 'blinds' }}
                </span>
            </div>

            <!-- Durum Badge -->
            <div class="px-3 py-1.5 rounded-full text-xs font-bold"
                :class="statusClass">
                {{ statusText }}
            </div>
        </div>

        <!-- İsim + Konum -->
        <div class="mt-6">
            <div class="flex items-center gap-2">
                <h3 class="font-bold text-lg">{{ name }}</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600">
                    PERDE
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ location }}</p>
        </div>

        <!-- Pozisyon Göstergesi -->
        <div class="mt-4">
            <div class="flex justify-between text-xs font-bold text-slate-400 mb-2">
                <span>Pozisyon</span>
                <span class="text-emerald-500">{{ position }}%</span>
            </div>
            
            <!-- Perde Görsel -->
            <div class="relative h-20 bg-slate-100 dark:bg-slate-800 rounded-xl overflow-hidden">
                <!-- Cam/Pencere -->
                <div class="absolute inset-0 bg-gradient-to-b from-sky-200 to-sky-100 dark:from-slate-700 dark:to-slate-600"></div>
                
                <!-- Perde -->
                <div class="absolute top-0 left-0 right-0 bg-gradient-to-b from-slate-300 to-slate-400 dark:from-slate-500 dark:to-slate-600 transition-all duration-500"
                    :style="{ height: (100 - position) + '%' }">
                    <!-- Perde çizgileri -->
                    <div class="absolute inset-0 flex flex-col justify-evenly">
                        <div v-for="i in 5" :key="i" class="border-b border-slate-400/30 dark:border-slate-400/20"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slider -->
        <div class="mt-4">
            <input type="range" min="0" max="100" v-model="position" @change="changePosition"
                class="w-full h-1.5 rounded-full appearance-none cursor-pointer"
                :style="sliderStyle" />
            <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                <span>Kapalı</span>
                <span>Açık</span>
            </div>
        </div>

        <!-- Hızlı Butonlar -->
        <div class="mt-4 flex gap-2">
            <button @click="setPosition(0)"
                class="flex-1 py-2 rounded-xl text-xs font-bold transition-all"
                :class="position === 0 
                    ? 'bg-slate-800 dark:bg-white text-white dark:text-slate-800' 
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'">
                <span class="material-symbols-outlined text-sm align-middle mr-1">blinds_closed</span>
                Kapat
            </button>
            <button @click="setPosition(50)"
                class="flex-1 py-2 rounded-xl text-xs font-bold transition-all"
                :class="position === 50 
                    ? 'bg-slate-800 dark:bg-white text-white dark:text-slate-800' 
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'">
                <span class="material-symbols-outlined text-sm align-middle mr-1">vertical_align_center</span>
                Yarı
            </button>
            <button @click="setPosition(100)"
                class="flex-1 py-2 rounded-xl text-xs font-bold transition-all"
                :class="position === 100 
                    ? 'bg-slate-800 dark:bg-white text-white dark:text-slate-800' 
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'">
                <span class="material-symbols-outlined text-sm align-middle mr-1">blinds</span>
                Aç
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    name: { type: String, default: 'Salon Perde' },
    location: { type: String, default: 'Salon' },
    initialPosition: { type: Number, default: 75 }
});

const emit = defineEmits(['command']);

const position = ref(props.initialPosition);
const isMoving = ref(false);

const statusText = computed(() => {
    if (isMoving.value) return 'Hareket Ediyor...';
    if (position.value === 0) return 'Kapalı';
    if (position.value === 100) return 'Tamamen Açık';
    return `${position.value}% Açık`;
});

const statusClass = computed(() => {
    if (isMoving.value) return 'bg-blue-100 dark:bg-blue-900/30 text-blue-600';
    if (position.value === 0) return 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400';
    return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600';
});

const sliderStyle = computed(() => {
    const percent = position.value;
    return {
        background: `linear-gradient(to right, #10b981 0%, #10b981 ${percent}%, #e2e8f0 ${percent}%, #e2e8f0 100%)`,
        '--thumb-color': '#10b981'
    };
});

const setPosition = (value) => {
    isMoving.value = true;
    setTimeout(() => {
        position.value = value;
        isMoving.value = false;
        emit('command', { type: 'position', value });
    }, 800);
};

const changePosition = () => {
    emit('command', { type: 'position', value: position.value });
};
</script>
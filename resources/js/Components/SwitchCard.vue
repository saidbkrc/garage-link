<template>
    <div class="group relative overflow-hidden p-6 rounded-[2rem] border transition-all bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md hover:shadow-xl">

        <!-- Üst: İkon + Genel Durum -->
        <div class="flex justify-between items-start relative z-10">
            <div class="size-12 rounded-2xl flex items-center justify-center transition-all"
                :class="anyOn 
                    ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-500' 
                    : 'bg-slate-200 dark:bg-slate-700 text-slate-400'">
                <span class="material-symbols-outlined text-3xl">toggle_on</span>
            </div>

            <!-- Durum Badge -->
            <div class="px-3 py-1.5 rounded-full text-xs font-bold"
                :class="anyOn 
                    ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600' 
                    : 'bg-slate-200 dark:bg-slate-700 text-slate-500'">
                {{ activeCount }}/{{ channels.length }} Açık
            </div>
        </div>

        <!-- İsim + Tip Badge -->
        <div class="mt-6">
            <div class="flex items-center gap-2">
                <h3 class="font-bold text-lg">{{ name }}</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600">
                    {{ channels.length }} GANG
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ location }}</p>
        </div>

        <!-- Kanal Butonları -->
        <div class="mt-4 grid gap-2" :class="channels.length <= 2 ? 'grid-cols-2' : 'grid-cols-2'">
            <button v-for="(channel, i) in channels" :key="i"
                @click="toggleChannel(i)"
                class="relative p-3 rounded-xl border-2 transition-all"
                :class="channel.isOn 
                    ? 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-400 dark:border-indigo-500' 
                    : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-slate-300'">
                
                <!-- Kanal Numarası -->
                <div class="absolute top-2 left-2 size-5 rounded-full flex items-center justify-center text-[10px] font-bold"
                    :class="channel.isOn 
                        ? 'bg-indigo-500 text-white' 
                        : 'bg-slate-200 dark:bg-slate-700 text-slate-500'">
                    {{ i + 1 }}
                </div>

                <!-- İkon -->
                <div class="flex flex-col items-center gap-2 pt-2">
                    <span class="material-symbols-outlined text-2xl transition-all"
                        :class="channel.isOn 
                            ? 'text-indigo-500' 
                            : 'text-slate-400'">
                        {{ channel.icon }}
                    </span>
                    <span class="text-xs font-medium"
                        :class="channel.isOn 
                            ? 'text-indigo-600 dark:text-indigo-400' 
                            : 'text-slate-500'">
                        {{ channel.name }}
                    </span>
                </div>

                <!-- On/Off Indicator -->
                <div class="absolute top-2 right-2 size-2 rounded-full"
                    :class="channel.isOn ? 'bg-green-500' : 'bg-slate-300 dark:bg-slate-600'"></div>
            </button>
        </div>

        <!-- Tümünü Aç/Kapat -->
        <div class="mt-4 flex gap-2">
            <button @click="setAll(false)"
                class="flex-1 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                Tümünü Kapat
            </button>
            <button @click="setAll(true)"
                class="flex-1 py-2 rounded-xl text-xs font-bold bg-indigo-500 text-white hover:bg-indigo-600 transition-all">
                Tümünü Aç
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    name: { type: String, default: 'Duvar Anahtarı' },
    location: { type: String, default: 'Yatak Odası' },
    initialChannels: {
        type: Array,
        default: () => [
            { name: 'Ana Işık', icon: 'light', isOn: true },
            { name: 'Abajur', icon: 'table_lamp', isOn: false },
            { name: 'Spot', icon: 'fluorescent', isOn: true },
            { name: 'Gece', icon: 'nightlight', isOn: false }
        ]
    }
});

const emit = defineEmits(['command']);

const channels = ref([...props.initialChannels]);

const anyOn = computed(() => channels.value.some(c => c.isOn));
const activeCount = computed(() => channels.value.filter(c => c.isOn).length);

const toggleChannel = (index) => {
    channels.value[index].isOn = !channels.value[index].isOn;
    emit('command', { 
        type: 'channel', 
        channel: index, 
        value: channels.value[index].isOn 
    });
};

const setAll = (state) => {
    channels.value.forEach(c => c.isOn = state);
    emit('command', { type: 'all', value: state });
};
</script>
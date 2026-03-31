<template>
    <div class="group relative overflow-hidden p-6 rounded-[2rem] border transition-all bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md hover:shadow-xl">

        <!-- Üst: İkon + Durum Badge -->
        <div class="flex justify-between items-start relative z-10">
            <div class="size-12 rounded-2xl flex items-center justify-center transition-all"
                :class="anyOn
                    ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-500'
                    : 'bg-slate-200 dark:bg-slate-700 text-slate-400'">
                <span class="material-symbols-outlined text-2xl">settings_input_component</span>
            </div>

            <div class="px-3 py-1.5 rounded-full text-xs font-bold"
                :class="anyOn
                    ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-600'
                    : 'bg-slate-200 dark:bg-slate-700 text-slate-500'">
                {{ activeCount }}/{{ channels.length }} Açık
            </div>
        </div>

        <!-- İsim + Kanal Badge -->
        <div class="mt-4">
            <div class="flex items-center gap-2">
                <h3 class="font-bold text-lg">{{ name }}</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-900/30 text-amber-600">
                    {{ channels.length }}CH
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ location }}</p>
        </div>

        <!-- Kanal Butonları -->
        <div class="mt-4 grid gap-2" :class="gridCols">
            <button v-for="(channel, i) in channels" :key="channel.endpoint"
                @click="toggleChannel(i)"
                class="relative p-3 rounded-xl border-2 transition-all flex flex-col items-center gap-1"
                :class="channel.isOn
                    ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-400 dark:border-amber-500'
                    : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-slate-300'">

                <!-- Endpoint numarası -->
                <div class="size-5 rounded-full flex items-center justify-center text-[10px] font-bold"
                    :class="channel.isOn
                        ? 'bg-amber-500 text-white'
                        : 'bg-slate-200 dark:bg-slate-700 text-slate-500'">
                    {{ channel.endpoint }}
                </div>

                <!-- On/Off göstergesi -->
                <div class="size-2 rounded-full"
                    :class="channel.isOn ? 'bg-green-500' : 'bg-slate-300 dark:bg-slate-600'"></div>

                <span class="text-[10px] font-medium leading-tight text-center"
                    :class="channel.isOn ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500'">
                    {{ channel.name }}
                </span>
            </button>
        </div>

        <!-- Tümünü Aç/Kapat -->
        <div class="mt-4 flex gap-2">
            <button @click="setAll(false)"
                class="flex-1 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                Tümünü Kapat
            </button>
            <button @click="setAll(true)"
                class="flex-1 py-2 rounded-xl text-xs font-bold bg-amber-500 text-white hover:bg-amber-600 transition-all">
                Tümünü Aç
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    name: { type: String, default: 'Röle Kartı' },
    location: { type: String, default: 'Bilinmiyor' },
    channelCount: { type: Number, default: 8 },
    initialChannels: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['command']);

const channels = ref(
    props.initialChannels.length
        ? [...props.initialChannels]
        : Array.from({ length: props.channelCount }, (_, i) => ({
            endpoint: i + 1,
            name: `Kanal ${i + 1}`,
            isOn: false,
        }))
);

const anyOn = computed(() => channels.value.some(c => c.isOn));
const activeCount = computed(() => channels.value.filter(c => c.isOn).length);
const gridCols = computed(() => props.channelCount <= 3 ? 'grid-cols-3' : 'grid-cols-4');

const toggleChannel = (index) => {
    channels.value[index].isOn = !channels.value[index].isOn;
    emit('command', {
        type: 'relay_channel',
        endpoint: channels.value[index].endpoint,
        value: channels.value[index].isOn,
    });
};

const setAll = (state) => {
    channels.value.forEach(c => c.isOn = state);
    emit('command', { type: 'relay_all', value: state });
};
</script>

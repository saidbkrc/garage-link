<template>
    <div class="group relative overflow-hidden p-6 rounded-[2rem] border transition-all bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md hover:shadow-xl">

        <!-- Online durumu göstergesi -->
        <div class="absolute top-4 right-4 flex items-center gap-1.5">
            <span class="size-2 rounded-full" :class="isOnline ? 'bg-green-500 animate-pulse' : 'bg-red-500'"></span>
            <span class="text-[10px] font-bold" :class="isOnline ? 'text-green-500' : 'text-red-500'">
                {{ isOnline ? 'ONLINE' : 'OFFLINE' }}
            </span>
        </div>

        <!-- Üst: İkon -->
        <div class="flex justify-between items-start relative z-10">
            <div class="size-12 rounded-2xl flex items-center justify-center transition-all"
                :class="sensorConfig[sensorType]?.iconBg || 'bg-blue-100 dark:bg-blue-900/40 text-blue-500'">
                <span class="material-symbols-outlined text-3xl">{{ sensorConfig[sensorType]?.icon || 'sensors' }}</span>
            </div>
        </div>

        <!-- İsim + Konum -->
        <div class="mt-6">
            <div class="flex items-center gap-2">
                <h3 class="font-bold text-lg">{{ name }}</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="sensorConfig[sensorType]?.badge || 'bg-blue-100 dark:bg-blue-900/30 text-blue-600'">
                    {{ sensorConfig[sensorType]?.label || 'SENSÖR' }}
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ location }}</p>
        </div>

        <!-- Değer Gösterimi -->
        <div class="mt-6 p-4 rounded-2xl" :class="sensorConfig[sensorType]?.valueBg || 'bg-slate-50 dark:bg-slate-800'">
            <div class="flex items-end justify-between">
                <div>
                    <p class="text-4xl font-bold" :class="sensorConfig[sensorType]?.valueText || 'text-blue-500'">
                        {{ displayValue }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">{{ sensorConfig[sensorType]?.description || 'Anlık değer' }}</p>
                </div>
                <div v-if="showTrend" class="flex items-center gap-1 text-xs font-bold"
                    :class="trendUp ? 'text-red-500' : 'text-green-500'">
                    <span class="material-symbols-outlined text-sm">
                        {{ trendUp ? 'trending_up' : 'trending_down' }}
                    </span>
                    {{ trend }}
                </div>
            </div>
        </div>

        <!-- Mini grafik (sadece sıcaklık ve nem için) -->
        <div v-if="showGraph" class="mt-3 flex items-end gap-0.5 h-10">
            <div v-for="(bar, i) in graphData" :key="i"
                class="flex-1 rounded-t transition-all"
                :class="sensorConfig[sensorType]?.barColor || 'bg-blue-400/40'"
                :style="{ height: bar + '%' }">
            </div>
        </div>

        <!-- Alt bilgi -->
        <div class="mt-4 flex items-center justify-between text-xs text-slate-400">
            <span>Son güncelleme: {{ lastUpdate }}</span>
            <button class="flex items-center gap-1 hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-sm">refresh</span>
                Yenile
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, default: 'Sensör' },
    location: { type: String, default: 'Salon' },
    sensorType: { type: String, default: 'temperature' }, // temperature, humidity, motion, door, smoke, light
    value: { type: [String, Number], default: 0 },
    unit: { type: String, default: '' },
    isOnline: { type: Boolean, default: true },
    trend: { type: String, default: '' },
    trendUp: { type: Boolean, default: false }
});

const sensorConfig = {
    temperature: {
        icon: 'thermostat',
        iconBg: 'bg-orange-100 dark:bg-orange-900/40 text-orange-500',
        badge: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600',
        label: 'SICAKLIK',
        valueBg: 'bg-orange-50 dark:bg-orange-900/20',
        valueText: 'text-orange-500',
        barColor: 'bg-orange-400/40',
        description: 'Ortam sıcaklığı'
    },
    humidity: {
        icon: 'humidity_percentage',
        iconBg: 'bg-blue-100 dark:bg-blue-900/40 text-blue-500',
        badge: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600',
        label: 'NEM',
        valueBg: 'bg-blue-50 dark:bg-blue-900/20',
        valueText: 'text-blue-500',
        barColor: 'bg-blue-400/40',
        description: 'Bağıl nem oranı'
    },
    motion: {
        icon: 'motion_sensor_active',
        iconBg: 'bg-purple-100 dark:bg-purple-900/40 text-purple-500',
        badge: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600',
        label: 'HAREKET',
        valueBg: 'bg-purple-50 dark:bg-purple-900/20',
        valueText: 'text-purple-500',
        barColor: 'bg-purple-400/40',
        description: 'Son hareket durumu'
    },
    door: {
        icon: 'door_open',
        iconBg: 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-500',
        badge: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600',
        label: 'KAPI',
        valueBg: 'bg-emerald-50 dark:bg-emerald-900/20',
        valueText: 'text-emerald-500',
        barColor: 'bg-emerald-400/40',
        description: 'Kapı durumu'
    },
    smoke: {
        icon: 'detector_smoke',
        iconBg: 'bg-red-100 dark:bg-red-900/40 text-red-500',
        badge: 'bg-red-100 dark:bg-red-900/30 text-red-600',
        label: 'DUMAN',
        valueBg: 'bg-red-50 dark:bg-red-900/20',
        valueText: 'text-red-500',
        barColor: 'bg-red-400/40',
        description: 'Duman algılama'
    },
    light: {
        icon: 'light_mode',
        iconBg: 'bg-amber-100 dark:bg-amber-900/40 text-amber-500',
        badge: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600',
        label: 'IŞIK',
        valueBg: 'bg-amber-50 dark:bg-amber-900/20',
        valueText: 'text-amber-500',
        barColor: 'bg-amber-400/40',
        description: 'Ortam aydınlığı'
    }
};

const displayValue = computed(() => {
    if (typeof props.value === 'number') {
        return props.value + (props.unit || '');
    }
    return props.value;
});

const showGraph = computed(() => ['temperature', 'humidity', 'light'].includes(props.sensorType));
const showTrend = computed(() => props.trend && ['temperature', 'humidity'].includes(props.sensorType));

const graphData = [35, 42, 38, 55, 48, 62, 58, 70, 65, 72, 68, 75];
const lastUpdate = '2 dakika önce';
</script>
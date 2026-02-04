<template>
    <div>
        <!-- Sekmeler -->
        <div class="flex border-b border-slate-200 dark:border-slate-800 gap-6 overflow-x-auto pb-px">
            <button v-for="tab in tabs" :key="tab.slug"
                @click="activeTab = tab.slug"
                class="relative pb-4 px-1 text-sm font-bold whitespace-nowrap transition-colors"
                :class="activeTab === tab.slug
                    ? 'text-primary'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                {{ tab.label }}
                <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px]"
                    :class="activeTab === tab.slug 
                        ? 'bg-primary/10 text-primary' 
                        : 'bg-slate-200 dark:bg-slate-700 text-slate-500'">
                    {{ tab.count }}
                </span>
                <!-- Active indicator -->
                <div v-if="activeTab === tab.slug" 
                    class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary rounded-full"></div>
            </button>
        </div>

        <!-- Aktif Sekme İçeriği -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mt-6">
            <template v-for="device in filteredDevices" :key="device.id">
                <!-- LED Şerit -->
                <LedStripCard v-if="device.type === 'led'"
                    :name="device.name"
                    :location="device.location"
                    :initialState="device.isOn"
                    :initialBrightness="device.brightness"
                    :initialColor="device.color"
                />

                <!-- Ampul -->
                <BulbCard v-else-if="device.type === 'bulb'"
                    :name="device.name"
                    :location="device.location"
                    :initialState="device.isOn"
                    :initialBrightness="device.brightness"
                    :initialTemperature="device.temperature"
                />

                <!-- Perde -->
                <CurtainCard v-else-if="device.type === 'curtain'"
                    :name="device.name"
                    :location="device.location"
                    :initialPosition="device.position"
                />

                <!-- Priz -->
                <PlugCard v-else-if="device.type === 'plug'"
                    :name="device.name"
                    :location="device.location"
                    :initialState="device.isOn"
                    :connectedDevice="device.connectedDevice"
                    :initialPower="device.power"
                />

                <!-- Anahtar -->
                <SwitchCard v-else-if="device.type === 'switch'"
                    :name="device.name"
                    :location="device.location"
                    :initialChannels="device.channels"
                />

                <!-- Klima / İklimlendirme -->
                <ClimateDeviceCard v-else-if="device.type === 'climate'"
                    :name="device.name"
                    :location="device.location"
                    :icon="device.icon"
                    :currentTemp="device.currentTemp"
                    :targetTemp="device.targetTemp"
                    :mode="device.mode"
                    :isOn="device.isOn"
                />

                <!-- Sensör -->
                <SensorCard v-else-if="device.type === 'sensor'"
                    :name="device.name"
                    :location="device.location"
                    :sensorType="device.sensorType"
                    :value="device.value"
                    :unit="device.unit"
                    :isOnline="device.isOnline"
                    :trend="device.trend"
                    :trendUp="device.trendUp"
                />

                <!-- Güvenlik -->
                <SecurityCard v-else-if="device.type === 'security'"
                    :name="device.name"
                    :location="device.location"
                    :deviceType="device.securityType"
                    :isLocked="device.isLocked"
                    :lastActivity="device.lastActivity"
                />
            </template>
        </div>

        <!-- Boş Durum -->
        <div v-if="filteredDevices.length === 0" 
            class="flex flex-col items-center justify-center py-16 text-slate-400">
            <span class="material-symbols-outlined text-5xl mb-3">devices_off</span>
            <p class="font-medium">Bu kategoride cihaz bulunamadı</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import LedStripCard from '@/Components/LedStripCard.vue';
import BulbCard from '@/Components/BulbCard.vue';
import CurtainCard from '@/Components/CurtainCard.vue';
import PlugCard from '@/Components/PlugCard.vue';
import SwitchCard from '@/Components/SwitchCard.vue';
import ClimateDeviceCard from '@/Components/ClimateDeviceCard.vue';
import SensorCard from '@/Components/SensorCard.vue';
import SecurityCard from '@/Components/SecurityCard.vue';

const activeTab = ref('all');

const tabs = [
    { slug: 'all', label: 'Tüm Cihazlar', count: 14 },
    { slug: 'lighting', label: 'Aydınlatma', count: 4 },
    { slug: 'climate', label: 'İklimlendirme', count: 2 },
    { slug: 'curtain', label: 'Perde & Motor', count: 2 },
    { slug: 'sensor', label: 'Sensörler', count: 3 },
    { slug: 'security', label: 'Güvenlik', count: 3 },
];

// Statik cihaz verileri
const allDevices = [
    // === AYDINLATMA ===
    {
        id: 1,
        type: 'led',
        category: 'lighting',
        name: 'TV Arkası LED',
        location: 'Salon',
        isOn: true,
        brightness: 85,
        color: '#A855F7'
    },
    {
        id: 2,
        type: 'bulb',
        category: 'lighting',
        name: 'Tavan Lambası',
        location: 'Yatak Odası',
        isOn: true,
        brightness: 70,
        temperature: 25
    },
    {
        id: 3,
        type: 'led',
        category: 'lighting',
        name: 'Mutfak Tezgah',
        location: 'Mutfak',
        isOn: true,
        brightness: 100,
        color: '#67E8F9'
    },
    {
        id: 4,
        type: 'switch',
        category: 'lighting',
        name: 'Yatak Odası Anahtar',
        location: 'Yatak Odası',
        channels: [
            { name: 'Ana Işık', icon: 'light', isOn: true },
            { name: 'Abajur', icon: 'table_lamp', isOn: false },
            { name: 'Spot', icon: 'fluorescent', isOn: true },
            { name: 'Gece', icon: 'nightlight', isOn: false }
        ]
    },

    // === İKLİMLENDİRME ===
    {
        id: 5,
        type: 'climate',
        category: 'climate',
        name: 'Salon Klima',
        location: 'Salon',
        icon: 'ac_unit',
        currentTemp: 26,
        targetTemp: 22,
        mode: 'cool',
        isOn: true
    },
    {
        id: 6,
        type: 'climate',
        category: 'climate',
        name: 'Yatak Odası Klima',
        location: 'Yatak Odası',
        icon: 'ac_unit',
        currentTemp: 24,
        targetTemp: 23,
        mode: 'auto',
        isOn: false
    },

    // === PERDE & MOTOR ===
    {
        id: 7,
        type: 'curtain',
        category: 'curtain',
        name: 'Salon Perde',
        location: 'Salon',
        position: 75
    },
    {
        id: 8,
        type: 'curtain',
        category: 'curtain',
        name: 'Yatak Odası Perde',
        location: 'Yatak Odası',
        position: 0
    },

    // === SENSÖRLER ===
    {
        id: 9,
        type: 'sensor',
        category: 'sensor',
        name: 'Salon Sıcaklık',
        location: 'Salon',
        sensorType: 'temperature',
        value: 24.5,
        unit: '°C',
        isOnline: true,
        trend: '+0.5°',
        trendUp: true
    },
    {
        id: 10,
        type: 'sensor',
        category: 'sensor',
        name: 'Nem Sensörü',
        location: 'Banyo',
        sensorType: 'humidity',
        value: 65,
        unit: '%',
        isOnline: true,
        trend: '-2%',
        trendUp: false
    },
    {
        id: 11,
        type: 'sensor',
        category: 'sensor',
        name: 'Hareket Sensörü',
        location: 'Koridor',
        sensorType: 'motion',
        value: 'Hareket Yok',
        unit: '',
        isOnline: true
    },

    // === GÜVENLİK ===
    {
        id: 12,
        type: 'security',
        category: 'security',
        name: 'Ana Kapı',
        location: 'Giriş',
        securityType: 'lock',
        isLocked: true,
        lastActivity: '14:32'
    },
    {
        id: 13,
        type: 'security',
        category: 'security',
        name: 'Garaj Kapısı',
        location: 'Garaj',
        securityType: 'garage',
        isLocked: true,
        lastActivity: '09:15'
    },
    {
        id: 14,
        type: 'security',
        category: 'security',
        name: 'Ev Alarmı',
        location: 'Genel',
        securityType: 'alarm',
        isLocked: false,
        lastActivity: '08:00'
    }
];

const filteredDevices = computed(() => {
    if (activeTab.value === 'all') return allDevices;
    return allDevices.filter(d => d.category === activeTab.value);
});
</script>
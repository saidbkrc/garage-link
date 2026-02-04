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
                <div v-if="activeTab === tab.slug" 
                    class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary rounded-full"></div>
            </button>
        </div>

        <!-- Oda Filtreleme (Tüm Cihazlar sekmesinde) -->
        <div v-if="activeTab === 'all' && rooms.length > 0" class="flex gap-2 mt-4 overflow-x-auto pb-2">
            <button @click="selectedRoom = null"
                class="px-4 py-2 rounded-xl text-sm font-medium whitespace-nowrap transition-all"
                :class="selectedRoom === null 
                    ? 'bg-primary text-white' 
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'">
                Tüm Odalar
            </button>
            <button v-for="room in rooms" :key="room.id"
                @click="selectedRoom = room.id"
                class="px-4 py-2 rounded-xl text-sm font-medium whitespace-nowrap transition-all flex items-center gap-2"
                :class="selectedRoom === room.id 
                    ? 'bg-primary text-white' 
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'">
                <span class="material-symbols-outlined text-lg">{{ room.icon || 'room' }}</span>
                {{ room.name }}
            </button>
        </div>

        <!-- Cihaz Kartları -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mt-6">
            <template v-for="device in filteredDevices" :key="device.id">
                <LedStripCard v-if="device.type === 'led_strip'"
                    :name="device.name"
                    :location="device.room || 'Bilinmiyor'"
                    :initialState="device.state?.power || false"
                    :initialBrightness="device.state?.brightness || 100"
                    :initialColor="device.state?.color || '#FFFFFF'"
                    @command="(cmd) => $emit('command', device.id, cmd)"
                />
                <BulbCard v-else-if="device.type === 'bulb'"
                    :name="device.name"
                    :location="device.room || 'Bilinmiyor'"
                    :initialState="device.state?.power || false"
                    :initialBrightness="device.state?.brightness || 100"
                    :initialTemperature="device.state?.temperature || 50"
                    @command="(cmd) => $emit('command', device.id, cmd)"
                />
                <CurtainCard v-else-if="device.type === 'curtain'"
                    :name="device.name"
                    :location="device.room || 'Bilinmiyor'"
                    :initialPosition="device.state?.position || 0"
                    @command="(cmd) => $emit('command', device.id, cmd)"
                />
                <PlugCard v-else-if="device.type === 'plug'"
                    :name="device.name"
                    :location="device.room || 'Bilinmiyor'"
                    :initialState="device.state?.power || false"
                    :connectedDevice="device.config?.connected_device || ''"
                    :initialPower="device.state?.power_watt || 0"
                    @command="(cmd) => $emit('command', device.id, cmd)"
                />
                <SwitchCard v-else-if="device.type === 'switch'"
                    :name="device.name"
                    :location="device.room || 'Bilinmiyor'"
                    :initialChannels="formatChannels(device)"
                    @command="(cmd) => $emit('command', device.id, cmd)"
                />
                <ClimateDeviceCard v-else-if="device.type === 'climate_ac'"
                    :name="device.name"
                    :location="device.room || 'Bilinmiyor'"
                    :currentTemp="device.state?.current_temp || 24"
                    :targetTemp="device.state?.target_temp || 22"
                    :mode="device.state?.mode || 'cool'"
                    :isOn="device.state?.power || false"
                    @command="(cmd) => $emit('command', device.id, cmd)"
                />
                <SensorCard v-else-if="device.category === 'sensor'"
                    :name="device.name"
                    :location="device.room || 'Bilinmiyor'"
                    :sensorType="getSensorType(device.type)"
                    :value="getSensorValue(device)"
                    :unit="getSensorUnit(device.type)"
                    :isOnline="device.is_online"
                />
                <SecurityCard v-else-if="device.category === 'security'"
                    :name="device.name"
                    :location="device.room || 'Bilinmiyor'"
                    :deviceType="getSecurityType(device.type)"
                    :isLocked="device.state?.locked ?? device.state?.armed ?? true"
                    :lastActivity="device.state?.last_activity || '-'"
                    @command="(cmd) => $emit('command', device.id, cmd)"
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

const props = defineProps({
    devices: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
});

const emit = defineEmits(['command']);

const activeTab = ref('all');
const selectedRoom = ref(null);

// Kategorilere göre cihaz sayısını hesapla
const tabs = computed(() => {
    const counts = {
        all: props.devices.length,
        lighting: props.devices.filter(d => d.category === 'lighting').length,
        climate: props.devices.filter(d => d.category === 'climate').length,
        curtain: props.devices.filter(d => d.category === 'curtain').length,
        plug: props.devices.filter(d => d.category === 'plug').length,
        sensor: props.devices.filter(d => d.category === 'sensor').length,
        security: props.devices.filter(d => d.category === 'security').length,
    };

    return [
        { slug: 'all', label: 'Tüm Cihazlar', count: counts.all },
        { slug: 'lighting', label: 'Aydınlatma', count: counts.lighting },
        { slug: 'climate', label: 'İklimlendirme', count: counts.climate },
        { slug: 'curtain', label: 'Perde & Motor', count: counts.curtain },
        { slug: 'plug', label: 'Prizler', count: counts.plug },
        { slug: 'sensor', label: 'Sensörler', count: counts.sensor },
        { slug: 'security', label: 'Güvenlik', count: counts.security },
    ].filter(tab => tab.count > 0 || tab.slug === 'all');
});

const filteredDevices = computed(() => {
    let devices = props.devices;

    // Kategori filtresi
    if (activeTab.value !== 'all') {
        devices = devices.filter(d => d.category === activeTab.value);
    }

    // Oda filtresi
    if (selectedRoom.value !== null) {
        devices = devices.filter(d => d.room_id === selectedRoom.value);
    }

    return devices;
});

// Helper fonksiyonlar
const formatChannels = (device) => {
    const channels = device.state?.channels || [];
    const names = device.config?.channel_names || ['Kanal 1', 'Kanal 2', 'Kanal 3', 'Kanal 4'];
    const icons = ['light', 'table_lamp', 'fluorescent', 'nightlight'];
    
    return channels.map((isOn, index) => ({
        name: names[index] || `Kanal ${index + 1}`,
        icon: icons[index] || 'light',
        isOn: isOn
    }));
};

const getSensorType = (type) => {
    const map = {
        'sensor_temperature': 'temperature',
        'sensor_humidity': 'humidity',
        'sensor_motion': 'motion',
    };
    return map[type] || 'temperature';
};

const getSensorValue = (device) => {
    if (device.type === 'sensor_temperature') {
        return device.state?.temperature || 0;
    }
    if (device.type === 'sensor_humidity') {
        return device.state?.humidity || 0;
    }
    if (device.type === 'sensor_motion') {
        return device.state?.motion ? 'Hareket Var' : 'Hareket Yok';
    }
    return 0;
};

const getSensorUnit = (type) => {
    const map = {
        'sensor_temperature': '°C',
        'sensor_humidity': '%',
        'sensor_motion': '',
    };
    return map[type] || '';
};

const getSecurityType = (type) => {
    const map = {
        'lock': 'lock',
        'garage': 'garage',
        'alarm': 'alarm',
    };
    return map[type] || 'lock';
};
</script>
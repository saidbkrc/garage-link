<template>
    <AppLayout title="Dashboard" currentPage="Dashboard" :user="user">

        <!-- İstatistikler -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatsCard label="Toplam Cihaz" :value="stats.total_devices" icon="memory" color="blue" />
            <StatsCard label="Çevrimiçi" :value="stats.online_devices" icon="wifi" color="emerald" />
            <StatsCard label="Enerji Kullanımı" :value="formattedEnergy" unit="kWh" icon="bolt" color="amber" />
            <StatsCard label="Aktif Alarmlar" :value="stats.active_alerts || 0" icon="warning" color="red" />
        </div>

        <!-- Hızlı Kontrol Bölümü -->
        <div class="pt-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Hızlı Kontrol</h2>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">Sık kullanılan cihazlarınızı hızlıca yönetin.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="handleGroupCommand('all_off')"
                        class="px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-sm transition-all">
                        Tümünü Kapat
                    </button>
                    <button @click="handleGroupCommand('all_on')"
                        class="px-5 py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-white font-semibold text-sm shadow-lg shadow-primary/20 transition-all">
                        Tümünü Aç
                    </button>
                </div>
            </div>

            <!-- Hızlı Kontrol Kartları (ilk 8 cihaz) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <template v-for="device in quickControlDevices" :key="device.id">
                    <LedStripCard v-if="device.type === 'led_strip'"
                        :name="device.name"
                        :location="device.room || 'Bilinmiyor'"
                        :initialState="device.state?.power || false"
                        :initialBrightness="device.state?.brightness || 100"
                        :initialColor="device.state?.color || '#FFFFFF'"
                        @command="(cmd) => handleCommand(device.id, cmd)"
                    />
                    <BulbCard v-else-if="device.type === 'bulb'"
                        :name="device.name"
                        :location="device.room || 'Bilinmiyor'"
                        :initialState="device.state?.power || false"
                        :initialBrightness="device.state?.brightness || 100"
                        :initialTemperature="device.state?.temperature || 50"
                        @command="(cmd) => handleCommand(device.id, cmd)"
                    />
                    <CurtainCard v-else-if="device.type === 'curtain'"
                        :name="device.name"
                        :location="device.room || 'Bilinmiyor'"
                        :initialPosition="device.state?.position || 0"
                        @command="(cmd) => handleCommand(device.id, cmd)"
                    />
                    <PlugCard v-else-if="device.type === 'plug'"
                        :name="device.name"
                        :location="device.room || 'Bilinmiyor'"
                        :initialState="device.state?.power || false"
                        :connectedDevice="device.config?.connected_device || ''"
                        :initialPower="device.state?.power_watt || 0"
                        @command="(cmd) => handleCommand(device.id, cmd)"
                    />
                    <SwitchCard v-else-if="device.type === 'switch'"
                        :name="device.name"
                        :location="device.room || 'Bilinmiyor'"
                        :initialChannels="formatChannels(device)"
                        @command="(cmd) => handleCommand(device.id, cmd)"
                    />
                    <ClimateDeviceCard v-else-if="device.type === 'climate_ac'"
                        :name="device.name"
                        :location="device.room || 'Bilinmiyor'"
                        :currentTemp="device.state?.current_temp || 24"
                        :targetTemp="device.state?.target_temp || 22"
                        :mode="device.state?.mode || 'cool'"
                        :isOn="device.state?.power || false"
                        @command="(cmd) => handleCommand(device.id, cmd)"
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
                        @command="(cmd) => handleCommand(device.id, cmd)"
                    />
                </template>
            </div>
        </div>

        <!-- Senaryolar -->
        <div v-if="scenes && scenes.length > 0" class="pt-8">
            <h2 class="text-2xl font-bold dark:text-white mb-4">Senaryolar</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <button v-for="scene in scenes" :key="scene.id"
                    @click="runScene(scene.id)"
                    class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:shadow-lg transition-all group">
                    <div class="size-12 rounded-xl flex items-center justify-center mb-3 transition-all"
                        :style="{ backgroundColor: scene.color + '20', color: scene.color }">
                        <span class="material-symbols-outlined text-2xl">{{ scene.icon || 'play_circle' }}</span>
                    </div>
                    <p class="font-bold text-sm">{{ scene.name }}</p>
                </button>
            </div>
        </div>

        <!-- Cihaz Yönetimi (Sekmeli) -->
        <div class="pt-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold dark:text-white">Cihaz Yönetimi</h2>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">Tüm akıllı ev cihazlarınız.</p>
                </div>
                <button class="px-5 py-2.5 rounded-xl bg-primary hover:bg-blue-600 text-white font-semibold text-sm flex items-center gap-2 self-start sm:self-auto transition-all">
                    <span class="material-symbols-outlined text-lg">add_circle</span>
                    Cihaz Ekle
                </button>
            </div>
            <DeviceTabsDynamic :devices="devices" :rooms="rooms" @command="handleCommand" />
        </div>

        <!-- Alt Grid: Enerji + Aktivite -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pt-6">
            <div class="lg:col-span-2">
                <EnergyChart />
            </div>
            <ActivityFeed :alerts="alerts" />
        </div>

        <!-- Bildirim Toast -->
        <Transition name="slide-up">
            <div v-if="commandFeedback"
                class="fixed bottom-5 right-5 z-50 bg-white dark:bg-slate-800 shadow-xl rounded-xl p-4 flex items-center gap-3 border border-slate-200 dark:border-slate-700">
                <span class="material-symbols-outlined text-emerald-500 filled-icon">check_circle</span>
                <span class="text-sm font-medium">{{ commandFeedback }}</span>
            </div>
        </Transition>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import LedStripCard from '@/Components/LedStripCard.vue';
import BulbCard from '@/Components/BulbCard.vue';
import CurtainCard from '@/Components/CurtainCard.vue';
import PlugCard from '@/Components/PlugCard.vue';
import SwitchCard from '@/Components/SwitchCard.vue';
import ClimateDeviceCard from '@/Components/ClimateDeviceCard.vue';
import SensorCard from '@/Components/SensorCard.vue';
import SecurityCard from '@/Components/SecurityCard.vue';
import DeviceTabsDynamic from '@/Components/DeviceTabsDynamic.vue';
import EnergyChart from '@/Components/EnergyChart.vue';
import ActivityFeed from '@/Components/ActivityFeed.vue';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    devices: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
    scenes: { type: Array, default: () => [] },
    alerts: { type: Array, default: () => [] },
    user: { type: Object, default: null }
});

const commandFeedback = ref('');

// Hızlı kontrol için ilk 8 cihaz
const quickControlDevices = computed(() => {
    return props.devices.slice(0, 8);
});

const showFeedback = (message) => {
    commandFeedback.value = message;
    setTimeout(() => commandFeedback.value = '', 2000);
};

const handleCommand = async (deviceId, command) => {
    console.log('Command:', deviceId, command);
    showFeedback(`Komut gönderildi: ${command.type}`);
    
    // API'ye gönder
    try {
        await fetch(`/api/v1/devices/${deviceId}/command`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                command_type: command.type,
                params: command,
            }),
        });
    } catch (error) {
        console.error('Command error:', error);
    }
};

const handleGroupCommand = async (type) => {
    showFeedback(type === 'all_on' ? 'Tüm cihazlar açılıyor...' : 'Tüm cihazlar kapatılıyor...');
};

const runScene = async (sceneId) => {
    showFeedback('Senaryo çalıştırılıyor...');
    try {
        await fetch(`/api/v1/scenes/${sceneId}/run`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        });
    } catch (error) {
        console.error('Scene error:', error);
    }
};

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

const formattedEnergy = computed(() => {
    const val = parseFloat(props.stats?.total_energy_today);
    return isNaN(val) ? '0.0' : val.toFixed(1);
});
</script>

<style>
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>
<template>
    <AppLayout title="Dashboard" currentPage="Dashboard" :user="user">

        <!-- Sahiplenilmemiş Gateway Bildirimi -->
        <div v-if="unclaimedGatewayCount > 0"
            class="mb-6 flex items-center gap-3 px-5 py-4 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
            <span class="material-symbols-outlined text-amber-500 filled-icon flex-shrink-0">router</span>
            <div class="flex-1">
                <p class="font-bold text-amber-800 dark:text-amber-300 text-sm">
                    {{ unclaimedGatewayCount }} yeni gateway tespit edildi
                </p>
                <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                    Cihazlarınızı kullanmak için gateway'i sahiplenin.
                </p>
            </div>
            <a href="/gateways"
                class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-semibold text-sm transition-all flex items-center gap-1.5 flex-shrink-0">
                <span class="material-symbols-outlined text-base">arrow_forward</span>
                Gateway Yönetimi
            </a>
        </div>

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
                    <!-- Cihaz durumu senkronizasyon butonu -->
                    <button @click="syncAllStates" :disabled="syncing"
                        class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:border-primary hover:text-primary font-semibold text-sm transition-all disabled:opacity-50"
                        title="Tüm cihazların gerçek durumunu sorgula">
                        <span class="material-symbols-outlined text-base" :class="syncing ? 'animate-spin' : ''">sync</span>
                        {{ syncing ? 'Sorgulanıyor...' : 'Senkronize Et' }}
                    </button>
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
                        :initialState="deviceState(device)?.power || false"
                        :initialBrightness="deviceState(device)?.brightness || 100"
                        :initialColor="rgbToHex(deviceState(device)?.color)"
                        @command="(cmd) => handleCommand(device.id, cmd)"
                    />
                    <BulbCard v-else-if="device.type === 'bulb'"
                        :name="device.name"
                        :location="device.room || 'Bilinmiyor'"
                        :initialState="deviceState(device)?.power || false"
                        :initialBrightness="deviceState(device)?.brightness || 100"
                        :initialTemperature="deviceState(device)?.temperature || 50"
                        @command="(cmd) => handleCommand(device.id, cmd)"
                    />
                    <CurtainCard v-else-if="device.type === 'curtain'"
                        :name="device.name"
                        :location="device.room || 'Bilinmiyor'"
                        :initialPosition="deviceState(device)?.position || 0"
                        @command="(cmd) => handleCommand(device.id, cmd)"
                    />
                    <PlugCard v-else-if="device.type === 'plug'"
                        :name="device.name"
                        :location="device.room || 'Bilinmiyor'"
                        :initialState="deviceState(device)?.power || false"
                        :connectedDevice="device.config?.connected_device || ''"
                        :initialPower="deviceState(device)?.power_watt || 0"
                        @command="(cmd) => handleCommand(device.id, cmd)"
                    />
                    <SwitchCard v-else-if="device.type === 'switch'"
                        :name="device.name"
                        :location="device.room || 'Bilinmiyor'"
                        :initialChannels="formatChannels(device)"
                        @command="(cmd) => handleCommand(device.id, cmd)"
                    />
                    <RelayCard v-else-if="device.type === 'relay'"
                        :name="device.name"
                        :location="device.room || 'Bilinmiyor'"
                        :channelCount="device.config?.channel_count || 8"
                        :initialChannels="formatRelayChannels(device)"
                        @command="(cmd) => handleCommand(device.id, cmd)"
                    />
                    <ClimateDeviceCard v-else-if="device.type === 'climate_ac'"
                        :name="device.name"
                        :location="device.room || 'Bilinmiyor'"
                        :currentTemp="deviceState(device)?.current_temp || 24"
                        :targetTemp="deviceState(device)?.target_temp || 22"
                        :mode="deviceState(device)?.mode || 'cool'"
                        :isOn="deviceState(device)?.power || false"
                        @command="(cmd) => handleCommand(device.id, cmd)"
                    />
                    <SensorCard v-else-if="device.category === 'sensor'"
                        :name="device.name"
                        :location="device.room || 'Bilinmiyor'"
                        :sensorType="getSensorType(device.type)"
                        :value="getSensorValue(device)"
                        :unit="getSensorUnit(device.type)"
                        :isOnline="deviceOnline(device)"
                    />
                    <SecurityCard v-else-if="device.category === 'security'"
                        :name="device.name"
                        :location="device.room || 'Bilinmiyor'"
                        :deviceType="getSecurityType(device.type)"
                        :isLocked="deviceState(device)?.locked ?? deviceState(device)?.armed ?? true"
                        :lastActivity="deviceState(device)?.last_activity || '-'"
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
import { ref, computed, reactive, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import LedStripCard from '@/Components/LedStripCard.vue';
import BulbCard from '@/Components/BulbCard.vue';
import CurtainCard from '@/Components/CurtainCard.vue';
import PlugCard from '@/Components/PlugCard.vue';
import SwitchCard from '@/Components/SwitchCard.vue';
import RelayCard from '@/Components/RelayCard.vue';
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
    user: { type: Object, default: null },
    unclaimedGatewayCount: { type: Number, default: 0 },
});

const commandFeedback = ref('');
const syncing = ref(false);

// Cihaz state'lerini tutacak yerel overlay map { [deviceId]: { state, is_online } }
// Inertia props immutable olduğu için komut sonrası / polling sonrası burayı güncelliyoruz
const liveStates = reactive({});

// Tüm cihaz state'lerini API'den çek
const fetchStates = async () => {
    try {
        const res = await fetch('/api/v1/devices/states', {
            headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) return;
        const data = await res.json();
        if (data.success) {
            Object.assign(liveStates, data.data);
        }
    } catch (e) {
        // sessizce geç, bir sonraki poll'da tekrar dener
    }
};

// Tüm cihazlara get_state MQTT gönder → firmware data topiğiyle cevap verir → handleData günceller
const syncAllStates = async () => {
    syncing.value = true;
    try {
        await fetch('/api/v1/devices/sync', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
        });
        // 2 saniye bekle (cihazlar cevap verecek), sonra DB'deki güncel state'i çek
        setTimeout(fetchStates, 2000);
        showFeedback('Cihaz durumları sorgulanıyor...');
    } catch (e) {
        console.error('sync error', e);
    } finally {
        setTimeout(() => { syncing.value = false; }, 2500);
    }
};

// State'i okurken liveStates öncelikli, yoksa Inertia prop
const deviceState = (device) => liveStates[device.id]?.state ?? device.state ?? {};
const deviceOnline = (device) => liveStates[device.id]?.is_online ?? device.is_online;

// 30 saniyede bir state'leri yenile
let refreshTimer = null;
onMounted(() => {
    fetchStates();
    refreshTimer = setInterval(fetchStates, 30000);
});
onUnmounted(() => {
    if (refreshTimer) clearInterval(refreshTimer);
});

// Hızlı kontrol — DashboardController zaten filtreliyor, slice gerekmez
const quickControlDevices = computed(() => props.devices);

// rgb(R, G, B) formatını #RRGGBB hex'e çevirir (LedStripCard için)
const rgbToHex = (color) => {
    if (!color) return '#FFFFFF';
    if (color.startsWith('#')) return color;
    const m = color.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
    if (!m) return '#FFFFFF';
    return '#' + [m[1], m[2], m[3]]
        .map(n => parseInt(n).toString(16).padStart(2, '0'))
        .join('');
};

const showFeedback = (message) => {
    commandFeedback.value = message;
    setTimeout(() => commandFeedback.value = '', 2000);
};

const handleCommand = async (deviceId, command) => {
    // DeviceCard { deviceId, slug, params } veya LedStripCard { type, value } formatını destekle
    let slug = command.slug ?? null;
    let params = { ...(command.params ?? {}) };

    if (!slug) {
        // LedStripCard / BulbCard gibi { type, value } formatı
        const type = command.type;
        if (type === 'power') {
            slug = command.value ? 'turn_on' : 'turn_off';
        } else if (type === 'brightness') {
            slug = 'brightness';
            params.brightness = parseInt(command.value);
        } else if (type === 'color') {
            slug = 'color';
            // hex → rgb() dönüşümü
            const rawColor = command.value ?? '';
            if (rawColor.startsWith('#')) {
                const hex = rawColor.replace('#', '');
                const r = parseInt(hex.slice(0, 2), 16);
                const g = parseInt(hex.slice(2, 4), 16);
                const b = parseInt(hex.slice(4, 6), 16);
                params.color = `rgb(${r}, ${g}, ${b})`;
            } else {
                params.color = rawColor;
            }
        } else if (type === 'color_temperature') {
            slug = 'color_temperature';
            params.temperature = command.value;
        } else if (type === 'position') {
            slug = 'set_position';
            params.position = command.value;
        } else if (type === 'channel') {
            slug = 'channel_toggle';
            params.channel = command.channel;
            params.value = command.value;
        } else if (type === 'relay_channel') {
            slug = command.value ? 'relay_turn_on' : 'relay_turn_off';
            params.endpoint = command.endpoint;
        } else if (type === 'relay_all') {
            slug = command.value ? 'turn_on' : 'turn_off';
        } else if (type === 'temperature') {
            slug = 'set_temperature';
            params.temperature = command.value;
        } else if (type === 'mode') {
            slug = 'set_mode';
            params.mode = command.value;
        } else if (type === 'lock') {
            slug = command.value ? 'lock' : 'unlock';
        } else {
            slug = type;
        }
    }

    if (!slug) return;

    showFeedback(`Komut: ${slug}`);

    try {
        const response = await fetch(`/api/v1/devices/${deviceId}/command`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ command_slug: slug, params }),
        });
        if (!response.ok) {
            const err = await response.json().catch(() => ({}));
            console.error('Command failed:', response.status, err);
        }
    } catch (error) {
        console.error('Command error:', error);
    }
};

const handleGroupCommand = async (type) => {
    const slug = type === 'all_on' ? 'group_all_on' : 'group_all_off';
    showFeedback(type === 'all_on' ? 'Tüm cihazlar açılıyor...' : 'Tüm cihazlar kapatılıyor...');
    try {
        await fetch('/api/v1/commands/group', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ command_slug: slug }),
        });
    } catch (error) {
        console.error('Group command error:', error);
    }
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
const formatRelayChannels = (device) => {
    const count = device.config?.channel_count || 8;
    const endpoints = device.config?.onoff_endpoints?.length
        ? device.config.onoff_endpoints
        : Array.from({ length: count }, (_, i) => i + 1);
    const states = deviceState(device)?.channels || {};
    return endpoints.map((ep, i) => ({
        endpoint: ep,
        name: `Kanal ${i + 1}`,
        isOn: states[ep] ?? false,
    }));
};

const formatChannels = (device) => {
    const channels = deviceState(device)?.channels || [];
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
    const s = deviceState(device);
    if (device.type === 'sensor_temperature') return s?.temperature || 0;
    if (device.type === 'sensor_humidity')    return s?.humidity || 0;
    if (device.type === 'sensor_motion')      return s?.motion ? 'Hareket Var' : 'Hareket Yok';
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
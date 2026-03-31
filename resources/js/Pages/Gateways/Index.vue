<template>
    <AppLayout title="Gateway Yönetimi" currentPage="Gateways" :user="user">

        <!-- Başlık -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gateway Yönetimi</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1">
                    Son güncelleme: {{ formatTime(lastRefreshed) }}
                    <span class="text-xs text-slate-400">(otomatik: 15s)</span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="scanAllGateways" :disabled="scanningAll"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl bg-primary/10 hover:bg-primary/20 text-primary font-medium text-sm transition-all disabled:opacity-50">
                    <span class="material-symbols-outlined text-base"
                        :class="scanningAll ? 'animate-spin' : ''">wifi_find</span>
                    {{ scanningAll ? 'Taranıyor...' : 'Tümünü Tara' }}
                </button>
                <button @click="refreshPage" :disabled="refreshing"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium text-sm transition-all disabled:opacity-50">
                    <span class="material-symbols-outlined text-base"
                        :class="refreshing ? 'animate-spin' : ''">refresh</span>
                    {{ refreshing ? 'Yenileniyor...' : 'Yenile' }}
                </button>
            </div>
        </div>

        <!-- Sahiplenilmemiş Gateway'ler (Claim Edilecekler) -->
        <div v-if="unclaimedGateways.length > 0" class="mb-8">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-amber-500">warning</span>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Bekleyen Gateway'ler</h2>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                    {{ unclaimedGateways.length }} yeni
                </span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div v-for="gw in unclaimedGateways" :key="gw.gateway_id"
                    class="p-5 rounded-2xl border-2 border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="size-2 rounded-full"
                                    :class="gw.is_actually_online ? 'bg-green-500 animate-pulse' : 'bg-slate-400'"></span>
                                <span class="text-xs font-bold"
                                    :class="gw.is_actually_online ? 'text-green-600 dark:text-green-400' : 'text-slate-400'">
                                    {{ gw.is_actually_online ? 'ONLINE' : 'OFFLINE' }}
                                </span>
                            </div>
                            <p class="font-mono text-sm font-bold text-slate-700 dark:text-slate-300">
                                {{ gw.gateway_id }}
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                Son görülme: {{ formatTime(gw.last_seen_at) }}
                            </p>
                        </div>
                        <span class="material-symbols-outlined text-amber-500 text-3xl">router</span>
                    </div>
                    <button
                        @click="claimGateway(gw.gateway_id)"
                        :disabled="claiming === gw.gateway_id"
                        class="mt-4 w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm transition-all flex items-center justify-center gap-2 disabled:opacity-60">
                        <span v-if="claiming === gw.gateway_id" class="material-symbols-outlined text-lg animate-spin">refresh</span>
                        <span v-else class="material-symbols-outlined text-lg">check_circle</span>
                        {{ claiming === gw.gateway_id ? 'Sahipleniliyor...' : 'Bu Gateway Benim' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Hiç gateway yok -->
        <div v-if="unclaimedGateways.length === 0 && myGateways.length === 0"
            class="mb-8 p-10 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 text-center">
            <span class="material-symbols-outlined text-5xl text-slate-400 mb-3 block">router</span>
            <p class="font-bold text-slate-600 dark:text-slate-400">Gateway bulunamadı</p>
            <p class="text-sm text-slate-500 mt-1">Gateway'i prize takın, birkaç saniye içinde burada görünecek.</p>
            <p class="text-xs text-slate-400 mt-3">MQTT subscriber çalışıyor olmalı: <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">php artisan mqtt:subscribe</code></p>
        </div>

        <!-- Bağlı (Online) Gateway'lerim -->
        <div v-if="myGateways.length > 0">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Bağlı Gateway'lerim</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <div v-for="gw in myGateways" :key="gw.gateway_id"
                    class="p-6 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm hover:shadow-md transition-all">

                    <!-- Üst Kısım -->
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="size-2 rounded-full"
                                    :class="gw.is_actually_online ? 'bg-green-500 animate-pulse' : 'bg-slate-400'"></span>
                                <span class="text-xs font-bold"
                                    :class="gw.is_actually_online ? 'text-green-600 dark:text-green-400' : 'text-slate-400'">
                                    {{ gw.is_actually_online ? 'ONLINE' : 'OFFLINE' }}
                                </span>
                            </div>
                            <h3 class="font-bold text-slate-900 dark:text-white">
                                {{ gw.name || 'Gateway' }}
                            </h3>
                            <p class="font-mono text-xs text-slate-500 mt-0.5">{{ gw.gateway_id }}</p>
                        </div>
                        <span class="material-symbols-outlined text-3xl text-primary">router</span>
                    </div>

                    <!-- İstatistikler -->
                    <div class="flex items-center gap-4 py-3 border-t border-slate-100 dark:border-slate-800">
                        <div class="text-center">
                            <p class="text-xl font-bold text-slate-900 dark:text-white">{{ gw.devices_count }}</p>
                            <p class="text-xs text-slate-500">Cihaz</p>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs text-slate-500">Son görülme</p>
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ formatTime(gw.last_seen_at) }}
                            </p>
                        </div>
                    </div>

                    <!-- Cihaz Tara Butonu -->
                    <button
                        @click="scanAndViewDevices(gw)"
                        :disabled="scanning === gw.gateway_id"
                        class="mt-3 w-full py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-white font-semibold text-sm transition-all flex items-center justify-center gap-2 shadow-sm shadow-primary/20 disabled:opacity-60">
                        <span class="material-symbols-outlined text-base"
                            :class="scanning === gw.gateway_id ? 'animate-spin' : ''">
                            {{ scanning === gw.gateway_id ? 'refresh' : 'radar' }}
                        </span>
                        {{ scanning === gw.gateway_id ? 'Taranıyor...' : 'Cihaz Tara' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Gateway Cihazları Modal -->
        <div v-if="showDevicesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">

                <!-- Modal Başlık -->
                <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="font-bold text-lg text-slate-900 dark:text-white">
                            {{ selectedGatewayName || 'Gateway' }} — Cihazları Yapılandır
                        </h3>
                        <p class="text-xs text-slate-500 font-mono mt-0.5">{{ selectedGatewayId }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="refreshDevices" :disabled="loadingDevices"
                            class="size-9 rounded-xl bg-primary/10 text-primary hover:bg-primary/20 flex items-center justify-center transition-all disabled:opacity-50"
                            title="Yenile">
                            <span class="material-symbols-outlined text-base"
                                :class="loadingDevices ? 'animate-spin' : ''">refresh</span>
                        </button>
                        <button @click="showDevicesModal = false"
                            class="size-9 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 flex items-center justify-center transition-all">
                            <span class="material-symbols-outlined text-slate-500">close</span>
                        </button>
                    </div>
                </div>

                <!-- Tarama notu -->
                <div v-if="scanSent" class="mx-6 mt-4 px-4 py-2.5 rounded-xl bg-primary/10 border border-primary/20 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-base animate-pulse">radar</span>
                    <p class="text-xs text-primary font-medium">
                        Tarama komutu gönderildi. Yeni cihazlar birkaç saniye içinde görünecek —
                        <button @click="refreshDevices" class="underline font-bold">Yenile</button>
                    </p>
                </div>

                <!-- Cihaz Listesi -->
                <div class="overflow-y-auto flex-1 p-6">
                    <div v-if="loadingDevices" class="flex items-center justify-center py-12">
                        <span class="material-symbols-outlined text-4xl text-slate-400 animate-spin">refresh</span>
                    </div>
                    <div v-else-if="gatewayDevices.length === 0" class="text-center py-12">
                        <span class="material-symbols-outlined text-5xl text-slate-400 block mb-3">devices_off</span>
                        <p class="text-slate-500 font-medium">Cihaz bulunamadı</p>
                        <p class="text-xs text-slate-400 mt-1">Tarama tamamlanınca cihazlar burada görünecek.</p>
                    </div>
                    <div v-else class="space-y-3">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                            {{ gatewayDevices.length }} cihaz bulundu —
                            {{ Object.values(deviceForms).filter(f => f.saved).length }} aktif
                        </p>

                        <div v-for="device in gatewayDevices" :key="device.id"
                            class="p-4 rounded-2xl border transition-all"
                            :class="deviceForms[device.id]?.saved
                                ? 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/10'
                                : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50'">

                            <!-- Üst satır: ikon + ieee + durum badge -->
                            <div class="flex items-center gap-3 mb-3">
                                <div class="size-9 rounded-xl flex items-center justify-center flex-shrink-0"
                                    :class="deviceForms[device.id]?.saved ? 'bg-green-100 text-green-600' : 'bg-primary/10 text-primary'">
                                    <span class="material-symbols-outlined text-lg">lightbulb</span>
                                </div>
                                <p class="text-xs text-slate-400 font-mono flex-1 truncate">{{ device.ieee_addr }}</p>
                                <span v-if="deviceForms[device.id]?.saved"
                                    class="text-xs font-bold px-2 py-0.5 rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 flex-shrink-0">
                                    ✓ Aktif
                                </span>
                                <span v-else
                                    class="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 flex-shrink-0">
                                    Yapılandır
                                </span>
                            </div>

                            <!-- İsim input -->
                            <input
                                v-if="deviceForms[device.id]"
                                v-model="deviceForms[device.id].name"
                                :disabled="deviceForms[device.id].saved"
                                placeholder="Cihaz adı (örn: Oturma Odası LED)"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm mb-2 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none disabled:opacity-60 transition-all" />

                            <!-- Cihaz tipi dropdown -->
                            <select
                                v-if="deviceForms[device.id]"
                                v-model="deviceForms[device.id].device_type_id"
                                :disabled="deviceForms[device.id].saved"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm mb-3 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none disabled:opacity-60 transition-all">
                                <option :value="null">— Cihaz tipi seç —</option>
                                <option v-for="dt in deviceTypes" :key="dt.id" :value="dt.id">{{ dt.name }}</option>
                            </select>

                            <!-- Kaydet butonu (tek cihaz) -->
                            <button
                                v-if="deviceForms[device.id] && !deviceForms[device.id].saved"
                                @click="saveDevice(device.id)"
                                :disabled="deviceForms[device.id].saving || !deviceForms[device.id].name"
                                class="w-full py-2 rounded-xl bg-primary hover:bg-primary/90 text-white text-sm font-semibold flex items-center justify-center gap-2 disabled:opacity-50 transition-all">
                                <span class="material-symbols-outlined text-base"
                                    :class="deviceForms[device.id].saving ? 'animate-spin' : ''">
                                    {{ deviceForms[device.id].saving ? 'refresh' : 'check_circle' }}
                                </span>
                                {{ deviceForms[device.id].saving ? 'Kaydediliyor...' : 'Kaydet & Aktifleştir' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer — Tümünü Kaydet -->
                <div v-if="gatewayDevices.length > 0"
                    class="p-5 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-4">
                    <p class="text-xs text-slate-400">
                        {{ Object.values(deviceForms).filter(f => f.saved).length }} / {{ gatewayDevices.length }} cihaz aktif
                    </p>
                    <button
                        @click="saveAllDevices"
                        :disabled="savingAll || Object.values(deviceForms).every(f => f.saved)"
                        class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-sm flex items-center gap-2 disabled:opacity-50 transition-all">
                        <span class="material-symbols-outlined text-base"
                            :class="savingAll ? 'animate-spin' : ''">
                            {{ savingAll ? 'refresh' : 'done_all' }}
                        </span>
                        {{ savingAll ? 'Kaydediliyor...' : 'Tümünü Kaydet' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <Transition name="slide-up">
            <div v-if="toast"
                class="fixed bottom-5 right-5 z-50 bg-white dark:bg-slate-800 shadow-xl rounded-xl p-4 flex items-center gap-3 border border-slate-200 dark:border-slate-700">
                <span class="material-symbols-outlined filled-icon"
                    :class="toast.type === 'success' ? 'text-emerald-500' : 'text-amber-500'">
                    {{ toast.type === 'success' ? 'check_circle' : 'info' }}
                </span>
                <span class="text-sm font-medium">{{ toast.message }}</span>
            </div>
        </Transition>

    </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    myGateways:        { type: Array, default: () => [] },
    unclaimedGateways: { type: Array, default: () => [] },
    user:              { type: Object, default: null },
});

const claiming = ref(null);
const scanning = ref(null);
const scanningAll = ref(false);
const toast = ref(null);
const refreshing = ref(false);
const lastRefreshed = ref(new Date());
let refreshInterval = null;

const refreshPage = () => {
    if (refreshing.value) return;
    refreshing.value = true;
    router.reload({
        only: ['myGateways', 'unclaimedGateways'],
        onFinish: () => {
            refreshing.value = false;
            lastRefreshed.value = new Date();
        },
    });
};

onMounted(() => {
    refreshInterval = setInterval(refreshPage, 15000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});
const showDevicesModal = ref(false);
const selectedGatewayId = ref('');
const selectedGatewayName = ref('');
const gatewayDevices = ref([]);
const loadingDevices = ref(false);
const scanSent = ref(false);

// Cihaz yapılandırma state
const deviceTypes = ref([]);
const deviceForms = ref({});
const savingAll = ref(false);

const showToast = (message, type = 'success') => {
    toast.value = { message, type };
    setTimeout(() => toast.value = null, 3000);
};

const getHeaders = () => ({
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
});

const scanAllGateways = async () => {
    scanningAll.value = true;
    try {
        const response = await fetch('/api/v1/gateways/scan-all', {
            method: 'POST',
            headers: getHeaders(),
        });
        const data = await response.json();
        if (data.success) {
            showToast(`${data.data.gateway_count} gateway taranıyor — cihazlar birkaç saniye içinde güncellenir.`);
            // 4 saniye sonra sayfayı yenile
            setTimeout(() => refreshPage(), 4000);
        } else {
            showToast('Tarama başlatılamadı', 'error');
        }
    } catch (error) {
        console.error('Scan all error:', error);
        showToast('Bağlantı hatası', 'error');
    } finally {
        scanningAll.value = false;
    }
};

const claimGateway = async (gatewayId) => {
    claiming.value = gatewayId;
    try {
        const response = await fetch(`/api/v1/gateways/${gatewayId}/claim`, {
            method: 'POST',
            headers: getHeaders(),
        });
        const data = await response.json();
        if (data.success) {
            showToast('Gateway başarıyla sahiplenildi! Cihazlar taranıyor...');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast('Bir hata oluştu', 'error');
        }
    } catch (error) {
        console.error('Claim error:', error);
        showToast('Bağlantı hatası', 'error');
    } finally {
        claiming.value = null;
    }
};

/**
 * Cihaz Tara: MQTT tarama komutu gönderir + cihaz listesi modalını açar.
 */
const scanAndViewDevices = async (gw) => {
    scanning.value = gw.gateway_id;
    selectedGatewayId.value = gw.gateway_id;
    selectedGatewayName.value = gw.name || 'Gateway';
    scanSent.value = false;
    showDevicesModal.value = true;

    // Önce mevcut cihazları yükle
    await loadDevices(gw.gateway_id);

    // Tarama komutunu gönder
    try {
        const response = await fetch(`/api/v1/gateways/${gw.gateway_id}/scan`, {
            method: 'POST',
            headers: getHeaders(),
        });
        const data = await response.json();
        if (data.success) {
            scanSent.value = true;
            // Gateway MQTT'ye cihaz listesini gönderir, DB'ye yazılması ~3 saniye sürer
            // Otomatik yenile
            setTimeout(() => {
                if (showDevicesModal.value && selectedGatewayId.value === gw.gateway_id) {
                    loadDevices(gw.gateway_id);
                }
            }, 3000);
        }
    } catch (error) {
        console.error('Scan error:', error);
    } finally {
        scanning.value = null;
    }
};

const loadDeviceTypes = async () => {
    if (deviceTypes.value.length) return;
    try {
        const res = await fetch('/api/v1/device-types', { headers: getHeaders() });
        const data = await res.json();
        if (data.success) deviceTypes.value = data.data;
    } catch (e) {
        console.error('Device types error:', e);
    }
};

const loadDevices = async (gatewayId) => {
    loadingDevices.value = true;
    gatewayDevices.value = [];
    deviceForms.value = {};
    try {
        const response = await fetch(`/api/v1/gateways/${gatewayId}/devices`, {
            headers: getHeaders(),
        });
        const data = await response.json();
        if (data.success) {
            gatewayDevices.value = data.data.devices;
            // Her cihaz için form state oluştur
            data.data.devices.forEach(d => {
                deviceForms.value[d.id] = {
                    name: d.name || '',
                    device_type_id: d.type_id ?? null,
                    saving: false,
                    saved: d.is_active,
                };
            });
        }
    } catch (error) {
        console.error('Devices error:', error);
    } finally {
        loadingDevices.value = false;
    }
    await loadDeviceTypes();
};

const saveDevice = async (deviceId) => {
    const form = deviceForms.value[deviceId];
    if (!form || form.saving || form.saved) return;
    form.saving = true;
    try {
        const res = await fetch(`/api/v1/devices/${deviceId}`, {
            method: 'PUT',
            headers: getHeaders(),
            body: JSON.stringify({
                name: form.name,
                device_type_id: form.device_type_id,
                is_active: true,
            }),
        });
        if (res.ok) {
            form.saved = true;
            showToast('Cihaz kaydedildi!');
        } else {
            showToast('Kayıt başarısız', 'error');
        }
    } catch (e) {
        console.error('Save device error:', e);
    } finally {
        form.saving = false;
    }
};

const saveAllDevices = async () => {
    savingAll.value = true;
    const devicesToSave = Object.entries(deviceForms.value)
        .filter(([, f]) => !f.saved && f.name)
        .map(([id, f]) => ({
            id: parseInt(id),
            name: f.name,
            device_type_id: f.device_type_id,
        }));

    if (!devicesToSave.length) {
        savingAll.value = false;
        return;
    }

    try {
        const res = await fetch(`/api/v1/gateways/${selectedGatewayId.value}/devices/configure`, {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify({ devices: devicesToSave }),
        });
        const data = await res.json();
        if (data.success) {
            // Kaydedilen tüm cihazları aktif işaretle
            devicesToSave.forEach(d => {
                if (deviceForms.value[d.id]) {
                    deviceForms.value[d.id].saved = true;
                }
            });
            showToast(`${data.data.saved_count} cihaz kaydedildi ve aktifleştirildi!`);
        } else {
            showToast('Kayıt başarısız', 'error');
        }
    } catch (e) {
        console.error('Save all error:', e);
    } finally {
        savingAll.value = false;
    }
};

const refreshDevices = () => {
    loadDevices(selectedGatewayId.value);
};

const formatTime = (timestamp) => {
    if (!timestamp) return 'Bilinmiyor';
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60) return 'Az önce';
    if (diff < 3600) return `${Math.floor(diff / 60)} dakika önce`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} saat önce`;
    return date.toLocaleDateString('tr-TR');
};
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

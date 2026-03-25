<template>
    <AppLayout title="Gateway Yönetimi" currentPage="Gateways" :user="user">

        <!-- Başlık -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gateway Yönetimi</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Bağlı ve bekleyen gateway'lerinizi yönetin.</p>
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
                                <span class="size-2 rounded-full bg-green-500 animate-pulse"></span>
                                <span class="text-xs font-bold text-green-600 dark:text-green-400">ONLINE</span>
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

        <!-- Boş durum (unclaimed) -->
        <div v-else-if="myGateways.length === 0" class="mb-8 p-8 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 text-center">
            <span class="material-symbols-outlined text-5xl text-slate-400 mb-3 block">router</span>
            <p class="font-bold text-slate-600 dark:text-slate-400">Gateway bulunamadı</p>
            <p class="text-sm text-slate-500 mt-1">Gateway'i prize takın, birkaç saniye içinde burada görünecek.</p>
        </div>

        <!-- Benim Gateway'lerim -->
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
                                    :class="gw.is_online ? 'bg-green-500 animate-pulse' : 'bg-slate-400'"></span>
                                <span class="text-xs font-bold"
                                    :class="gw.is_online ? 'text-green-600 dark:text-green-400' : 'text-slate-500'">
                                    {{ gw.is_online ? 'ONLINE' : 'OFFLINE' }}
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

                    <!-- Aksiyonlar -->
                    <div class="flex gap-2 mt-3">
                        <button
                            @click="scanGateway(gw.gateway_id)"
                            :disabled="scanning === gw.gateway_id"
                            class="flex-1 py-2 rounded-xl border border-primary text-primary hover:bg-primary hover:text-white font-semibold text-sm transition-all flex items-center justify-center gap-1.5 disabled:opacity-60">
                            <span class="material-symbols-outlined text-base"
                                :class="scanning === gw.gateway_id ? 'animate-spin' : ''">
                                {{ scanning === gw.gateway_id ? 'refresh' : 'radar' }}
                            </span>
                            {{ scanning === gw.gateway_id ? 'Taranıyor...' : 'Cihaz Tara' }}
                        </button>
                        <button
                            @click="viewDevices(gw.gateway_id)"
                            class="flex-1 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-sm transition-all flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-base">devices</span>
                            Cihazlar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gateway Cihazları Modal -->
        <div v-if="showDevicesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col">
                <!-- Modal Başlık -->
                <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="font-bold text-lg text-slate-900 dark:text-white">Gateway Cihazları</h3>
                        <p class="text-xs text-slate-500 font-mono mt-0.5">{{ selectedGatewayId }}</p>
                    </div>
                    <button @click="showDevicesModal = false"
                        class="size-9 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 flex items-center justify-center transition-all">
                        <span class="material-symbols-outlined text-slate-500">close</span>
                    </button>
                </div>

                <!-- Cihaz Listesi -->
                <div class="overflow-y-auto flex-1 p-6">
                    <div v-if="loadingDevices" class="flex items-center justify-center py-12">
                        <span class="material-symbols-outlined text-4xl text-slate-400 animate-spin">refresh</span>
                    </div>
                    <div v-else-if="gatewayDevices.length === 0" class="text-center py-12">
                        <span class="material-symbols-outlined text-5xl text-slate-400 block mb-3">devices_off</span>
                        <p class="text-slate-500">Henüz cihaz bulunamadı.</p>
                        <p class="text-xs text-slate-400 mt-1">Cihaz eklemek için gateway'i tarayın.</p>
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="device in gatewayDevices" :key="device.id"
                            class="flex items-center gap-4 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                            <div class="size-10 rounded-xl flex items-center justify-center"
                                :class="device.is_active ? 'bg-primary/10 text-primary' : 'bg-slate-200 dark:bg-slate-700 text-slate-500'">
                                <span class="material-symbols-outlined text-xl">lightbulb</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-sm text-slate-900 dark:text-white truncate">{{ device.name }}</p>
                                <p class="text-xs text-slate-500 font-mono">{{ device.ieee_addr }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                                    :class="device.is_active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'">
                                    {{ device.is_active ? 'Aktif' : 'Yapılandır' }}
                                </span>
                                <p class="text-xs text-slate-500 mt-0.5">{{ device.type_name }}</p>
                            </div>
                        </div>
                    </div>
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
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    myGateways:        { type: Array, default: () => [] },
    unclaimedGateways: { type: Array, default: () => [] },
    user:              { type: Object, default: null },
});

const claiming = ref(null);
const scanning = ref(null);
const toast = ref(null);
const showDevicesModal = ref(false);
const selectedGatewayId = ref('');
const gatewayDevices = ref([]);
const loadingDevices = ref(false);

const showToast = (message, type = 'success') => {
    toast.value = { message, type };
    setTimeout(() => toast.value = null, 3000);
};

const getHeaders = () => ({
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
});

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
            // Sayfayı yenile
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

const scanGateway = async (gatewayId) => {
    scanning.value = gatewayId;
    try {
        const response = await fetch(`/api/v1/gateways/${gatewayId}/scan`, {
            method: 'POST',
            headers: getHeaders(),
        });
        const data = await response.json();
        if (data.success) {
            showToast('Cihaz taraması başlatıldı. Birkaç saniye bekleyin...');
        }
    } catch (error) {
        console.error('Scan error:', error);
        showToast('Tarama başlatılamadı', 'error');
    } finally {
        setTimeout(() => scanning.value = null, 2000);
    }
};

const viewDevices = async (gatewayId) => {
    selectedGatewayId.value = gatewayId;
    showDevicesModal.value = true;
    loadingDevices.value = true;
    gatewayDevices.value = [];

    try {
        const response = await fetch(`/api/v1/gateways/${gatewayId}/devices`, {
            headers: getHeaders(),
        });
        const data = await response.json();
        if (data.success) {
            gatewayDevices.value = data.data.devices;
        }
    } catch (error) {
        console.error('Devices error:', error);
    } finally {
        loadingDevices.value = false;
    }
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

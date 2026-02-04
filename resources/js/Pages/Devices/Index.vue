<template>
    <AppLayout title="Cihazlar" currentPage="Cihazlar" :user="user">
        
        <!-- Page Heading -->
        <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
            <div class="flex flex-col gap-1">
                <h2 class="text-3xl font-black tracking-tight">Cihazlar</h2>
                <p class="text-slate-500 dark:text-slate-400">Tüm akıllı ev cihazlarınızı buradan kolayca yönetin ve izleyin.</p>
            </div>
            <button @click="showAddModal = true"
                class="flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-bold hover:bg-primary/90 transition-all shadow-lg shadow-primary/20">
                <span class="material-symbols-outlined">add</span>
                Cihaz Ekle
            </button>
        </div>

        <!-- Filters & Toolbar -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 mb-6 flex flex-wrap items-center gap-4">
            <!-- Search -->
            <div class="flex-1 min-w-[300px]">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                    <input v-model="filters.search"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-100 dark:bg-slate-800 border-none rounded-lg text-sm focus:ring-2 focus:ring-primary/50 transition-all"
                        placeholder="Cihaz ara..." type="text" />
                </div>
            </div>

            <!-- Room Filter -->
            <select v-model="filters.room"
                class="bg-slate-100 dark:bg-slate-800 border-none rounded-lg text-sm py-2.5 pl-4 pr-10 focus:ring-2 focus:ring-primary/50">
                <option value="">Tüm Odalar</option>
                <option v-for="room in rooms" :key="room.id" :value="room.id">{{ room.name }}</option>
            </select>

            <!-- Category Filter -->
            <select v-model="filters.category"
                class="bg-slate-100 dark:bg-slate-800 border-none rounded-lg text-sm py-2.5 pl-4 pr-10 focus:ring-2 focus:ring-primary/50">
                <option value="">Tüm Kategoriler</option>
                <option value="lighting">Aydınlatma</option>
                <option value="climate">İklimlendirme</option>
                <option value="security">Güvenlik</option>
                <option value="plug">Enerji</option>
                <option value="sensor">Sensörler</option>
                <option value="curtain">Perde & Motor</option>
            </select>

            <div class="h-8 w-px bg-slate-200 dark:bg-slate-800 mx-1"></div>

            <!-- Status Filter -->
            <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-lg">
                <button @click="filters.status = ''"
                    class="px-4 py-1.5 text-sm font-semibold rounded-md transition-all"
                    :class="filters.status === '' ? 'bg-white dark:bg-slate-700 shadow-sm' : 'text-slate-500 hover:text-primary'">
                    Tümü
                </button>
                <button @click="filters.status = 'online'"
                    class="px-4 py-1.5 text-sm font-medium rounded-md transition-all"
                    :class="filters.status === 'online' ? 'bg-white dark:bg-slate-700 shadow-sm' : 'text-slate-500 hover:text-primary'">
                    Çevrimiçi
                </button>
                <button @click="filters.status = 'offline'"
                    class="px-4 py-1.5 text-sm font-medium rounded-md transition-all"
                    :class="filters.status === 'offline' ? 'bg-white dark:bg-slate-700 shadow-sm' : 'text-slate-500 hover:text-primary'">
                    Çevrimdışı
                </button>
            </div>

            <!-- View Toggle -->
            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg ml-auto">
                <button @click="viewMode = 'grid'"
                    class="p-1.5 rounded-md transition-all"
                    :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary' : 'text-slate-500 hover:bg-white dark:hover:bg-slate-700'">
                    <span class="material-symbols-outlined text-[20px]">grid_view</span>
                </button>
                <button @click="viewMode = 'table'"
                    class="p-1.5 rounded-md transition-all"
                    :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary' : 'text-slate-500 hover:bg-white dark:hover:bg-slate-700'">
                    <span class="material-symbols-outlined text-[20px]">list</span>
                </button>
            </div>
        </div>

        <!-- Table View -->
        <div v-if="viewMode === 'table'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="p-4 w-12 text-center">
                                <input type="checkbox" v-model="selectAll" @change="toggleSelectAll"
                                    class="rounded border-slate-300 text-primary focus:ring-primary" />
                            </th>
                            <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500">Cihaz</th>
                            <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500">Kategori</th>
                            <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500">Durum</th>
                            <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500">Anlık Veri</th>
                            <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500">Son Görülme</th>
                            <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        <tr v-for="device in filteredDevices" :key="device.id"
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 text-center">
                                <input type="checkbox" v-model="selectedDevices" :value="device.id"
                                    class="rounded border-slate-300 text-primary focus:ring-primary" />
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="size-10 rounded-lg flex items-center justify-center"
                                        :class="getCategoryColor(device.category)">
                                        <span class="material-symbols-outlined">{{ device.icon || 'memory' }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold">{{ device.name }}</p>
                                        <p class="text-xs text-slate-500">{{ device.room || 'Oda atanmamış' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="getCategoryBadge(device.category)">
                                    {{ getCategoryLabel(device.category) }}
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <span class="size-2 rounded-full"
                                        :class="device.is_online ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                    <span class="text-sm font-medium">{{ device.is_online ? 'Çevrimiçi' : 'Çevrimdışı' }}</span>
                                </div>
                            </td>
                            <td class="p-4">
                                <p class="text-sm" :class="device.is_online ? 'font-medium' : 'text-slate-400 italic'">
                                    {{ getDeviceStateText(device) }}
                                </p>
                            </td>
                            <td class="p-4 text-sm text-slate-500">{{ getLastSeen(device) }}</td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="editDevice(device)"
                                        class="p-2 text-slate-400 hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </button>
                                    <button @click="showDeviceMenu(device)"
                                        class="p-2 text-slate-400 hover:text-red-500 transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">more_vert</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 bg-slate-50 dark:bg-slate-800/30 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <p class="text-xs font-medium text-slate-500">
                    Toplam <b>{{ filteredDevices.length }}</b> cihaz
                </p>
                <div class="flex items-center gap-2">
                    <button class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-400 hover:bg-white dark:hover:bg-slate-700 transition-all disabled:opacity-50">
                        <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                    </button>
                    <button class="size-8 rounded-lg bg-primary text-white text-xs font-bold">1</button>
                    <button class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-400 hover:bg-white dark:hover:bg-slate-700 transition-all">
                        <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Grid View -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <div v-for="device in filteredDevices" :key="device.id"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 hover:shadow-lg transition-all cursor-pointer"
                @click="editDevice(device)">
                <div class="flex items-start justify-between mb-4">
                    <div class="size-12 rounded-xl flex items-center justify-center"
                        :class="getCategoryColor(device.category)">
                        <span class="material-symbols-outlined text-2xl">{{ device.icon || 'memory' }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="size-2 rounded-full" :class="device.is_online ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                        <span class="text-xs font-medium" :class="device.is_online ? 'text-emerald-600' : 'text-slate-400'">
                            {{ device.is_online ? 'Çevrimiçi' : 'Çevrimdışı' }}
                        </span>
                    </div>
                </div>
                <h3 class="font-bold text-sm mb-1">{{ device.name }}</h3>
                <p class="text-xs text-slate-500 mb-3">{{ device.room || 'Oda atanmamış' }}</p>
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium"
                        :class="getCategoryBadge(device.category)">
                        {{ getCategoryLabel(device.category) }}
                    </span>
                    <span class="text-xs text-slate-400">{{ getLastSeen(device) }}</span>
                </div>
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-4">
                <div class="size-12 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined">sensors</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Aktif Cihazlar</p>
                    <p class="text-2xl font-black">{{ stats.online }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-4">
                <div class="size-12 bg-amber-100 dark:bg-amber-900/30 text-amber-600 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined">warning</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Çevrimdışı</p>
                    <p class="text-2xl font-black">{{ stats.offline }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-4">
                <div class="size-12 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined">trending_up</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Toplam Cihaz</p>
                    <p class="text-2xl font-black">{{ stats.total }}</p>
                </div>
            </div>
        </div>

        <!-- Bulk Actions Bar -->
        <Transition name="slide-up">
            <div v-if="selectedDevices.length > 0"
                class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-4">
                <span class="text-sm font-medium">{{ selectedDevices.length }} cihaz seçildi</span>
                <div class="h-6 w-px bg-slate-700 dark:bg-slate-300"></div>
                <button class="px-4 py-1.5 bg-emerald-500 text-white rounded-lg text-sm font-bold hover:bg-emerald-600 transition-colors">
                    Tümünü Aç
                </button>
                <button class="px-4 py-1.5 bg-slate-700 dark:bg-slate-200 text-white dark:text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-600 transition-colors">
                    Tümünü Kapat
                </button>
                <button class="px-4 py-1.5 bg-red-500 text-white rounded-lg text-sm font-bold hover:bg-red-600 transition-colors">
                    Sil
                </button>
                <button @click="selectedDevices = []" class="p-1.5 hover:bg-slate-800 dark:hover:bg-slate-100 rounded-lg transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
        </Transition>

        <!-- Add Device Modal -->
        <AddDeviceModal 
            v-if="showAddModal" 
            :rooms="rooms" 
            :device-types="deviceTypes"
            @close="showAddModal = false" 
            @submit="handleAddDevice" 
        />

    </AppLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import AddDeviceModal from '@/Components/AddDeviceModal.vue';

const props = defineProps({
    devices: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
    deviceTypes: { type: Array, default: () => [] },
    user: { type: Object, default: null }
});

const showAddModal = ref(false);
const viewMode = ref('table');
const selectAll = ref(false);
const selectedDevices = ref([]);

const filters = reactive({
    search: '',
    room: '',
    category: '',
    status: ''
});

// Stats
const stats = computed(() => ({
    total: props.devices.length,
    online: props.devices.filter(d => d.is_online).length,
    offline: props.devices.filter(d => !d.is_online).length
}));

// Filtered devices
const filteredDevices = computed(() => {
    return props.devices.filter(device => {
        // Search filter
        if (filters.search && !device.name.toLowerCase().includes(filters.search.toLowerCase())) {
            return false;
        }
        // Room filter
        if (filters.room && device.room_id !== filters.room) {
            return false;
        }
        // Category filter
        if (filters.category && device.category !== filters.category) {
            return false;
        }
        // Status filter
        if (filters.status === 'online' && !device.is_online) {
            return false;
        }
        if (filters.status === 'offline' && device.is_online) {
            return false;
        }
        return true;
    });
});

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedDevices.value = filteredDevices.value.map(d => d.id);
    } else {
        selectedDevices.value = [];
    }
};

const getCategoryColor = (category) => {
    const colors = {
        lighting: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600',
        climate: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600',
        security: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600',
        plug: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600',
        sensor: 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600',
        curtain: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600',
    };
    return colors[category] || 'bg-slate-100 dark:bg-slate-800 text-slate-600';
};

const getCategoryBadge = (category) => {
    const badges = {
        lighting: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        climate: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
        security: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
        plug: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
        sensor: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300',
        curtain: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    };
    return badges[category] || 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300';
};

const getCategoryLabel = (category) => {
    const labels = {
        lighting: 'Aydınlatma',
        climate: 'İklimlendirme',
        security: 'Güvenlik',
        plug: 'Enerji',
        sensor: 'Sensör',
        curtain: 'Perde',
    };
    return labels[category] || category || 'Diğer';
};

const getDeviceStateText = (device) => {
    if (!device.is_online) return 'Sinyal Yok';
    
    const state = device.state || {};
    
    if (device.category === 'lighting') {
        if (state.power) {
            return `Açık, %${state.brightness || 100} Parlaklık`;
        }
        return 'Kapalı';
    }
    
    if (device.category === 'climate') {
        return `${state.current_temp || state.target_temp || 22}°C`;
    }
    
    if (device.category === 'plug') {
        return state.power ? `Açık, ${state.power_watt || 0}W` : 'Kapalı';
    }
    
    if (device.category === 'curtain') {
        return `%${state.position || 0} Açık`;
    }
    
    if (device.category === 'sensor') {
        if (state.temperature) return `${state.temperature}°C`;
        if (state.humidity) return `%${state.humidity} Nem`;
        if (state.motion !== undefined) return state.motion ? 'Hareket Var' : 'Hareket Yok';
    }
    
    if (device.category === 'security') {
        return state.locked || state.armed ? 'Güvenli' : 'Açık';
    }
    
    return 'Aktif';
};

const getLastSeen = (device) => {
    if (device.is_online) return 'Şimdi';
    // Mock data - gerçek uygulamada last_seen_at kullanılacak
    return '5 dk önce';
};

const editDevice = (device) => {
    console.log('Edit device:', device);
    // TODO: Edit modal veya sayfa
};

const showDeviceMenu = (device) => {
    console.log('Show menu:', device);
    // TODO: Context menu
};

const handleAddDevice = (deviceData) => {
    console.log('Add device:', deviceData);
    showAddModal.value = false;
    // TODO: API call
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
    transform: translate(-50%, 20px);
}
</style>
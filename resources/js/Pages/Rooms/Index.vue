<template>
    <AppLayout title="Odalar" currentPage="Odalar" :user="user">
        
        <!-- Page Heading -->
        <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
            <div class="flex flex-col gap-1">
                <h2 class="text-3xl font-black tracking-tight">Odalar</h2>
                <p class="text-slate-500 dark:text-slate-400">Cihazlarınızı odalara göre organize edin</p>
            </div>
            <button @click="openAddModal"
                class="flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-bold hover:bg-primary/90 transition-all shadow-lg shadow-primary/20">
                <span class="material-symbols-outlined">add</span>
                Oda Ekle
            </button>
        </div>

        <!-- Room Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div v-for="room in rooms" :key="room.id"
                class="room-card group relative p-6 rounded-2xl hover:shadow-xl hover:-translate-y-1 transition-all cursor-pointer"
                :class="getRoomCardClass(room.color)"
                @click="viewRoomDevices(room)">
                
                <!-- Header -->
                <div class="flex justify-between items-start mb-4">
                    <div class="size-12 text-white rounded-xl flex items-center justify-center shadow-lg"
                        :class="getRoomIconClass(room.color)">
                        <span class="material-symbols-outlined text-2xl">{{ room.icon || 'meeting_room' }}</span>
                    </div>
                    
                    <!-- Device Type Icons -->
                    <div class="flex gap-1">
                        <span v-for="(icon, i) in getRoomDeviceIcons(room)" :key="i"
                            class="material-symbols-outlined text-sm"
                            :class="getRoomTextClass(room.color)">
                            {{ icon }}
                        </span>
                    </div>
                </div>

                <!-- Room Info -->
                <h3 class="text-xl font-bold mb-1">{{ room.name }}</h3>
                <p class="text-sm font-medium" :class="getRoomTextClass(room.color)">
                    {{ room.device_count || 0 }} cihaz
                    <template v-if="room.online_count > 0">
                        <span :class="getRoomTextClass(room.color, 0.7)">•</span>
                        {{ room.online_count }} çevrimiçi
                    </template>
                </p>

                <!-- Hover Button -->
                <div class="mt-6 flex justify-between items-center">
                    <div class="flex gap-1">
                        <button @click.stop="editRoom(room)"
                            class="p-2 rounded-lg opacity-0 group-hover:opacity-100 transition-all hover:bg-white/50 dark:hover:bg-black/20"
                            :class="getRoomTextClass(room.color)">
                            <span class="material-symbols-outlined text-lg">edit</span>
                        </button>
                        <button @click.stop="confirmDeleteRoom(room)"
                            class="p-2 rounded-lg opacity-0 group-hover:opacity-100 transition-all hover:bg-white/50 dark:hover:bg-black/20 text-red-500">
                            <span class="material-symbols-outlined text-lg">delete</span>
                        </button>
                    </div>
                    <button class="view-button bg-primary text-white text-xs font-bold px-4 py-2 rounded-lg opacity-0 group-hover:opacity-100 transform translate-y-2 group-hover:translate-y-0 transition-all">
                        Cihazları Gör
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="rooms.length === 0" class="col-span-full flex flex-col items-center justify-center py-16 text-slate-400">
                <span class="material-symbols-outlined text-6xl mb-4">meeting_room</span>
                <p class="font-bold text-lg mb-2">Henüz oda eklenmemiş</p>
                <p class="text-sm mb-6">Cihazlarınızı organize etmek için oda ekleyin</p>
                <button @click="openAddModal"
                    class="flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-bold hover:bg-primary/90 transition-all">
                    <span class="material-symbols-outlined">add</span>
                    İlk Odanızı Ekleyin
                </button>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-4">
                <div class="size-12 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined">meeting_room</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Toplam Oda</p>
                    <p class="text-2xl font-black">{{ stats.totalRooms }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-4">
                <div class="size-12 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined">devices</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Atanmış Cihaz</p>
                    <p class="text-2xl font-black">{{ stats.assignedDevices }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center gap-4">
                <div class="size-12 bg-amber-100 dark:bg-amber-900/30 text-amber-600 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined">warning</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Atanmamış Cihaz</p>
                    <p class="text-2xl font-black" :class="stats.unassignedDevices > 0 ? 'text-amber-600' : ''">
                        {{ stats.unassignedDevices }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Add/Edit Room Modal -->
        <RoomModal 
            v-if="showModal" 
            :room="selectedRoom"
            :devices="unassignedDevices"
            @close="closeModal" 
            @submit="handleSubmit" 
        />

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteConfirm = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="size-12 bg-red-100 dark:bg-red-900/30 text-red-600 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl">warning</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">Odayı Sil</h3>
                            <p class="text-sm text-slate-500">Bu işlem geri alınamaz</p>
                        </div>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">
                        <strong>{{ roomToDelete?.name }}</strong> odasını silmek istediğinizden emin misiniz? 
                        Bu odadaki cihazlar "atanmamış" durumuna geçecektir.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button @click="showDeleteConfirm = false"
                            class="px-4 py-2 rounded-xl font-medium text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            İptal
                        </button>
                        <button @click="deleteRoom"
                            class="px-4 py-2 rounded-xl font-bold bg-red-500 text-white hover:bg-red-600 transition-colors">
                            Evet, Sil
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import RoomModal from '@/Components/RoomModal.vue';

const props = defineProps({
    rooms: { type: Array, default: () => [] },
    devices: { type: Array, default: () => [] },
    user: { type: Object, default: null }
});

const showModal = ref(false);
const selectedRoom = ref(null);
const showDeleteConfirm = ref(false);
const roomToDelete = ref(null);

// Stats
const stats = computed(() => ({
    totalRooms: props.rooms.length,
    assignedDevices: props.devices.filter(d => d.room_id).length,
    unassignedDevices: props.devices.filter(d => !d.room_id).length
}));

// Unassigned devices for modal
const unassignedDevices = computed(() => {
    return props.devices.filter(d => !d.room_id || d.room_id === selectedRoom.value?.id);
});

// Color classes
const colorClasses = {
    blue: {
        card: 'bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/30',
        icon: 'bg-blue-500 shadow-blue-500/20',
        text: 'text-blue-600 dark:text-blue-400',
        textFaded: 'text-blue-400/70'
    },
    purple: {
        card: 'bg-purple-50/50 dark:bg-purple-900/10 border border-purple-100 dark:border-purple-900/30',
        icon: 'bg-purple-500 shadow-purple-500/20',
        text: 'text-purple-600 dark:text-purple-400',
        textFaded: 'text-purple-400/70'
    },
    orange: {
        card: 'bg-orange-50/50 dark:bg-orange-900/10 border border-orange-100 dark:border-orange-900/30',
        icon: 'bg-orange-500 shadow-orange-500/20',
        text: 'text-orange-600 dark:text-orange-400',
        textFaded: 'text-orange-400/70'
    },
    cyan: {
        card: 'bg-cyan-50/50 dark:bg-cyan-900/10 border border-cyan-100 dark:border-cyan-900/30',
        icon: 'bg-cyan-500 shadow-cyan-500/20',
        text: 'text-cyan-600 dark:text-cyan-400',
        textFaded: 'text-cyan-400/70'
    },
    emerald: {
        card: 'bg-emerald-50/50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-900/30',
        icon: 'bg-emerald-500 shadow-emerald-500/20',
        text: 'text-emerald-600 dark:text-emerald-400',
        textFaded: 'text-emerald-400/70'
    },
    teal: {
        card: 'bg-teal-50/50 dark:bg-teal-900/10 border border-teal-100 dark:border-teal-900/30',
        icon: 'bg-teal-500 shadow-teal-500/20',
        text: 'text-teal-600 dark:text-teal-400',
        textFaded: 'text-teal-400/70'
    },
    amber: {
        card: 'bg-amber-50/50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30',
        icon: 'bg-amber-500 shadow-amber-500/20',
        text: 'text-amber-600 dark:text-amber-400',
        textFaded: 'text-amber-400/70'
    },
    red: {
        card: 'bg-red-50/50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30',
        icon: 'bg-red-500 shadow-red-500/20',
        text: 'text-red-600 dark:text-red-400',
        textFaded: 'text-red-400/70'
    },
    pink: {
        card: 'bg-pink-50/50 dark:bg-pink-900/10 border border-pink-100 dark:border-pink-900/30',
        icon: 'bg-pink-500 shadow-pink-500/20',
        text: 'text-pink-600 dark:text-pink-400',
        textFaded: 'text-pink-400/70'
    }
};

const getRoomCardClass = (color) => colorClasses[color]?.card || colorClasses.blue.card;
const getRoomIconClass = (color) => colorClasses[color]?.icon || colorClasses.blue.icon;
const getRoomTextClass = (color, opacity) => {
    if (opacity) return colorClasses[color]?.textFaded || colorClasses.blue.textFaded;
    return colorClasses[color]?.text || colorClasses.blue.text;
};

const getRoomDeviceIcons = (room) => {
    // Get unique device icons for this room (max 5)
    const roomDevices = props.devices.filter(d => d.room_id === room.id);
    const icons = [...new Set(roomDevices.map(d => d.icon || 'memory'))];
    return icons.slice(0, 5);
};

const openAddModal = () => {
    selectedRoom.value = null;
    showModal.value = true;
};

const editRoom = (room) => {
    selectedRoom.value = room;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedRoom.value = null;
};

const handleSubmit = (data) => {
    if (selectedRoom.value) {
        // Update
        router.put(`/rooms/${selectedRoom.value.id}`, data, {
            onSuccess: () => closeModal()
        });
    } else {
        // Create
        router.post('/rooms', data, {
            onSuccess: () => closeModal()
        });
    }
};

const confirmDeleteRoom = (room) => {
    roomToDelete.value = room;
    showDeleteConfirm.value = true;
};

const deleteRoom = () => {
    router.delete(`/rooms/${roomToDelete.value.id}`, {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            roomToDelete.value = null;
        }
    });
};

const viewRoomDevices = (room) => {
    router.visit(`/devices?room=${room.id}`);
};
</script>

<style>
.room-card .view-button {
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.room-card:hover .view-button {
    opacity: 1;
    transform: translateY(0);
}
</style>
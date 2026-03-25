<template>
    <AppLayout title="Senaryolar" currentPage="Senaryolar" :user="user">
        
        <!-- Page Heading -->
        <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-black tracking-tight">Senaryolar</h2>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Tek dokunuşla birden fazla cihazı kontrol edin</p>
            </div>
            <button @click="openCreateModal"
                class="bg-primary hover:bg-blue-600 text-white px-6 py-3 rounded-xl font-bold flex items-center gap-2 transition-all shadow-lg shadow-primary/20 hover:shadow-primary/40 active:scale-95">
                <span class="material-symbols-outlined">add_circle</span>
                Senaryo Oluştur
            </button>
        </div>

        <!-- Scene Cards Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div v-for="scene in scenes" :key="scene.id"
                class="group bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 flex flex-col transition-all hover:shadow-xl hover:shadow-slate-200/50 dark:hover:shadow-none hover:-translate-y-1">
                
                <!-- Header -->
                <div class="flex justify-between items-start mb-6">
                    <div class="flex items-center gap-4">
                        <div class="size-14 rounded-2xl flex items-center justify-center"
                            :class="getSceneIconBg(scene.color)">
                            <span class="material-symbols-outlined text-3xl icon-fill">{{ scene.icon || 'play_circle' }}</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">{{ scene.name }}</h3>
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">
                                {{ getActionCount(scene) }} cihaz etkileniyor
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="editScene(scene)"
                            class="size-10 flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <span class="material-symbols-outlined">edit</span>
                        </button>
                        <button @click="confirmDeleteScene(scene)"
                            class="size-10 flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-end justify-between mt-auto">
                    <!-- Action Device Icons -->
                    <div class="flex gap-2">
                        <div v-for="(icon, i) in getActionIcons(scene)" :key="i"
                            class="size-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500">
                            <span class="material-symbols-outlined text-lg">{{ icon }}</span>
                        </div>
                    </div>

                    <!-- Play Button -->
                    <div class="relative">
                        <button @click="runScene(scene)"
                            :disabled="runningSceneId === scene.id"
                            class="peer size-14 bg-primary text-white rounded-full flex items-center justify-center transition-all hover:scale-110 active:scale-95 shadow-lg shadow-primary/25 disabled:opacity-70 disabled:hover:scale-100">
                            <span v-if="runningSceneId === scene.id" class="material-symbols-outlined text-2xl animate-spin">progress_activity</span>
                            <span v-else class="material-symbols-outlined text-3xl icon-fill ml-1">play_arrow</span>
                        </button>
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-3 px-3 py-1.5 bg-slate-900 text-white text-[10px] font-bold rounded-lg opacity-0 invisible peer-hover:opacity-100 peer-hover:visible transition-all whitespace-nowrap pointer-events-none">
                            Senaryoyu Çalıştır
                            <div class="absolute top-full left-1/2 -translate-x-1/2 border-8 border-transparent border-t-slate-900"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="scenes.length === 0" class="col-span-full flex flex-col items-center justify-center py-16 text-slate-400">
                <span class="material-symbols-outlined text-6xl mb-4">auto_awesome</span>
                <p class="font-bold text-lg mb-2">Henüz senaryo oluşturulmamış</p>
                <p class="text-sm mb-6">Cihazlarınızı tek dokunuşla kontrol etmek için senaryo oluşturun</p>
                <button @click="openCreateModal"
                    class="flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-bold hover:bg-primary/90 transition-all">
                    <span class="material-symbols-outlined">add</span>
                    İlk Senaryonuzu Oluşturun
                </button>
            </div>
        </div>

        <!-- Stats Footer -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6">
            <div class="flex flex-wrap gap-8 justify-between lg:justify-start">
                <div class="flex flex-col">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Toplam Senaryo</span>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-2xl font-black">{{ scenes.length }}</span>
                    </div>
                </div>
                <div class="w-px h-12 bg-slate-200 dark:bg-slate-800 hidden lg:block"></div>
                <div class="flex flex-col">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">En Çok Kullanılan</span>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-2xl font-black">{{ stats.mostUsed?.name || '-' }}</span>
                        <span v-if="stats.mostUsed?.count" class="text-sm font-medium text-slate-500">({{ stats.mostUsed.count }} kez)</span>
                    </div>
                </div>
                <div class="w-px h-12 bg-slate-200 dark:bg-slate-800 hidden lg:block"></div>
                <div class="flex flex-col">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Son Çalıştırılan</span>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-2xl font-black">{{ stats.lastRun?.name || '-' }}</span>
                        <span v-if="stats.lastRun?.time" class="text-sm font-medium text-primary">{{ stats.lastRun.time }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Scene Modal -->
        <SceneModal 
            v-if="showModal" 
            :scene="selectedScene"
            :devices="devices"
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
                            <h3 class="text-lg font-bold">Senaryoyu Sil</h3>
                            <p class="text-sm text-slate-500">Bu işlem geri alınamaz</p>
                        </div>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">
                        <strong>{{ sceneToDelete?.name }}</strong> senaryosunu silmek istediğinizden emin misiniz?
                    </p>
                    <div class="flex justify-end gap-3">
                        <button @click="showDeleteConfirm = false"
                            class="px-4 py-2 rounded-xl font-medium text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            İptal
                        </button>
                        <button @click="deleteScene"
                            class="px-4 py-2 rounded-xl font-bold bg-red-500 text-white hover:bg-red-600 transition-colors">
                            Evet, Sil
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Toast Notification -->
        <Transition name="slide-up">
            <div v-if="toast.show"
                class="fixed bottom-6 right-6 z-50 bg-white dark:bg-slate-800 shadow-xl rounded-xl p-4 flex items-center gap-3 border border-slate-200 dark:border-slate-700">
                <span class="material-symbols-outlined" :class="toast.success ? 'text-emerald-500' : 'text-red-500'">
                    {{ toast.success ? 'check_circle' : 'error' }}
                </span>
                <span class="text-sm font-medium">{{ toast.message }}</span>
            </div>
        </Transition>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SceneModal from '@/Components/SceneModal.vue';

const props = defineProps({
    scenes: { type: Array, default: () => [] },
    devices: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    user: { type: Object, default: null }
});

const showModal = ref(false);
const selectedScene = ref(null);
const showDeleteConfirm = ref(false);
const sceneToDelete = ref(null);
const runningSceneId = ref(null);

const toast = ref({
    show: false,
    message: '',
    success: true
});

const showToast = (message, success = true) => {
    toast.value = { show: true, message, success };
    setTimeout(() => toast.value.show = false, 3000);
};

// Color classes
const colorClasses = {
    emerald: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
    green: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
    orange: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400',
    purple: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
    red: 'bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400',
    rose: 'bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400',
    blue: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    cyan: 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400',
    amber: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
    pink: 'bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400',
};

const getSceneIconBg = (color) => {
    return colorClasses[color] || colorClasses.blue;
};

const getActionCount = (scene) => {
    return scene.actions?.length || 0;
};

const getActionIcons = (scene) => {
    // Get unique device icons from actions
    const icons = [];
    const actions = scene.actions || [];
    
    actions.forEach(action => {
        const device = props.devices.find(d => d.id === action.device_id);
        if (device?.icon && !icons.includes(device.icon)) {
            icons.push(device.icon);
        }
    });
    
    // Default icons if no devices found
    if (icons.length === 0) {
        return ['lightbulb', 'curtains', 'thermostat'].slice(0, 3);
    }
    
    return icons.slice(0, 5);
};

const openCreateModal = () => {
    selectedScene.value = null;
    showModal.value = true;
};

const editScene = (scene) => {
    selectedScene.value = scene;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedScene.value = null;
};

const handleSubmit = (data) => {
    if (selectedScene.value) {
        router.put(`/scenes/${selectedScene.value.id}`, data, {
            onSuccess: () => {
                closeModal();
                showToast('Senaryo güncellendi');
            }
        });
    } else {
        router.post('/scenes', data, {
            onSuccess: () => {
                closeModal();
                showToast('Senaryo oluşturuldu');
            }
        });
    }
};

const confirmDeleteScene = (scene) => {
    sceneToDelete.value = scene;
    showDeleteConfirm.value = true;
};

const deleteScene = () => {
    router.delete(`/scenes/${sceneToDelete.value.id}`, {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            sceneToDelete.value = null;
            showToast('Senaryo silindi');
        }
    });
};

const runScene = async (scene) => {
    runningSceneId.value = scene.id;
    showToast(`${scene.name} senaryosu çalıştırılıyor...`);
    
    try {
        await router.post(`/scenes/${scene.id}/run`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                showToast(`${scene.name} senaryosu çalıştırıldı`, true);
            },
            onError: () => {
                showToast('Senaryo çalıştırılamadı', false);
            },
            onFinish: () => {
                runningSceneId.value = null;
            }
        });
    } catch (e) {
        runningSceneId.value = null;
        showToast('Bir hata oluştu', false);
    }
};
</script>

<style>
.icon-fill {
    font-variation-settings: 'FILL' 1;
}
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
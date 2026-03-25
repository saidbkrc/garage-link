<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('close')"></div>
            
            <!-- Modal -->
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col">
                
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="text-xl font-bold">{{ isEditing ? 'Senaryoyu Düzenle' : 'Yeni Senaryo Oluştur' }}</h3>
                        <p class="text-sm text-slate-500 mt-1">Cihazlarınızı tek dokunuşla kontrol edin</p>
                    </div>
                    <button @click="$emit('close')"
                        class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Form -->
                <div class="p-6 space-y-6 overflow-y-auto flex-1">
                    
                    <!-- Scene Name -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Senaryo Adı</label>
                        <input v-model="form.name" type="text" required
                            class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary/50"
                            placeholder="Örn: Eve Geldim, Uyku Modu" />
                    </div>

                    <!-- Icon and Color Row -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Icon Picker -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">İkon</label>
                            <div class="grid grid-cols-5 gap-2">
                                <button v-for="icon in sceneIcons" :key="icon" type="button"
                                    @click="form.icon = icon"
                                    class="size-10 rounded-xl flex items-center justify-center transition-all"
                                    :class="form.icon === icon 
                                        ? 'bg-primary text-white' 
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'">
                                    <span class="material-symbols-outlined">{{ icon }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Color Picker -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Renk</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="color in colors" :key="color.value" type="button"
                                    @click="form.color = color.value"
                                    class="size-10 rounded-xl transition-all flex items-center justify-center"
                                    :class="[
                                        color.class,
                                        form.color === color.value ? 'ring-2 ring-offset-2 ring-slate-400' : ''
                                    ]">
                                    <span v-if="form.color === color.value" class="material-symbols-outlined text-white text-sm">check</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Section -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Aksiyonlar</label>
                            <button @click="addAction" type="button"
                                class="text-sm font-medium text-primary hover:text-primary/80 flex items-center gap-1">
                                <span class="material-symbols-outlined text-lg">add</span>
                                Aksiyon Ekle
                            </button>
                        </div>

                        <!-- Action List -->
                        <div class="space-y-3">
                            <div v-for="(action, index) in form.actions" :key="index"
                                class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                                <div class="flex items-start gap-3">
                                    <!-- Device Select -->
                                    <div class="flex-1 space-y-2">
                                        <select v-model="action.device_id"
                                            class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-primary/50">
                                            <option value="">Cihaz seçin</option>
                                            <option v-for="device in devices" :key="device.id" :value="device.id">
                                                {{ device.name }} ({{ device.room || 'Oda yok' }})
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Command Select -->
                                    <div class="flex-1 space-y-2">
                                        <select v-model="action.command"
                                            class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-primary/50">
                                            <option value="">Komut seçin</option>
                                            <option value="turn_on">Aç</option>
                                            <option value="turn_off">Kapat</option>
                                            <option value="set_brightness">Parlaklık Ayarla</option>
                                            <option value="set_color">Renk Ayarla</option>
                                            <option value="set_temperature">Sıcaklık Ayarla</option>
                                            <option value="set_position">Pozisyon Ayarla</option>
                                            <option value="lock">Kilitle</option>
                                            <option value="unlock">Kilidi Aç</option>
                                            <option value="arm">Alarmı Aktif Et</option>
                                            <option value="disarm">Alarmı Devre Dışı</option>
                                        </select>
                                    </div>

                                    <!-- Remove Button -->
                                    <button @click="removeAction(index)" type="button"
                                        class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                </div>

                                <!-- Action Parameters -->
                                <div v-if="action.command && needsParams(action.command)" class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                    <!-- Brightness Slider -->
                                    <div v-if="action.command === 'set_brightness'" class="flex items-center gap-3">
                                        <span class="text-xs text-slate-500 w-16">Parlaklık:</span>
                                        <input type="range" v-model="action.params.brightness" min="0" max="100"
                                            class="flex-1 h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer" />
                                        <span class="text-sm font-bold w-12 text-right">%{{ action.params.brightness || 100 }}</span>
                                    </div>

                                    <!-- Temperature Input -->
                                    <div v-if="action.command === 'set_temperature'" class="flex items-center gap-3">
                                        <span class="text-xs text-slate-500 w-16">Sıcaklık:</span>
                                        <input type="number" v-model="action.params.temperature" min="16" max="30"
                                            class="w-20 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm" />
                                        <span class="text-sm">°C</span>
                                    </div>

                                    <!-- Position Slider -->
                                    <div v-if="action.command === 'set_position'" class="flex items-center gap-3">
                                        <span class="text-xs text-slate-500 w-16">Pozisyon:</span>
                                        <input type="range" v-model="action.params.position" min="0" max="100"
                                            class="flex-1 h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer" />
                                        <span class="text-sm font-bold w-12 text-right">%{{ action.params.position || 0 }}</span>
                                    </div>

                                    <!-- Color Picker -->
                                    <div v-if="action.command === 'set_color'" class="flex items-center gap-3">
                                        <span class="text-xs text-slate-500 w-16">Renk:</span>
                                        <input type="color" v-model="action.params.color"
                                            class="w-10 h-8 rounded cursor-pointer" />
                                        <span class="text-sm font-mono">{{ action.params.color || '#FFFFFF' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty Actions State -->
                            <div v-if="form.actions.length === 0"
                                class="p-8 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl text-center">
                                <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">playlist_add</span>
                                <p class="text-sm text-slate-400">Henüz aksiyon eklenmedi</p>
                                <button @click="addAction" type="button"
                                    class="mt-3 text-sm font-medium text-primary hover:text-primary/80">
                                    + İlk aksiyonu ekle
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Önizleme</label>
                        <div class="p-4 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700">
                            <div class="flex items-center gap-4">
                                <div class="size-12 rounded-xl flex items-center justify-center"
                                    :class="getPreviewIconClass">
                                    <span class="material-symbols-outlined text-2xl">{{ form.icon || 'play_circle' }}</span>
                                </div>
                                <div>
                                    <h4 class="font-bold">{{ form.name || 'Senaryo Adı' }}</h4>
                                    <p class="text-xs text-slate-500">{{ form.actions.length }} aksiyon</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <div v-if="error" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                        <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 p-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                    <button @click="$emit('close')" type="button"
                        class="px-5 py-2.5 rounded-xl font-medium text-slate-600 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        İptal
                    </button>
                    <button @click="handleSubmit" :disabled="isLoading"
                        class="px-5 py-2.5 rounded-xl font-bold bg-primary text-white hover:bg-primary/90 transition-colors flex items-center gap-2 disabled:opacity-70">
                        <svg v-if="isLoading" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isLoading ? 'Kaydediliyor...' : (isEditing ? 'Kaydet' : 'Senaryo Oluştur') }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';

const props = defineProps({
    scene: { type: Object, default: null },
    devices: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'submit']);

const isLoading = ref(false);
const error = ref('');

const isEditing = computed(() => !!props.scene);

const form = reactive({
    name: '',
    icon: 'home',
    color: 'emerald',
    actions: []
});

// Watch for scene prop changes (edit mode)
watch(() => props.scene, (newScene) => {
    if (newScene) {
        form.name = newScene.name || '';
        form.icon = newScene.icon || 'home';
        form.color = newScene.color || 'emerald';
        form.actions = (newScene.actions || []).map(a => ({
            device_id: a.device_id,
            command: a.command,
            params: { ...a.params }
        }));
    } else {
        form.name = '';
        form.icon = 'home';
        form.color = 'emerald';
        form.actions = [];
    }
}, { immediate: true });

const sceneIcons = [
    'home', 'directions_walk', 'bedtime', 'movie', 'celebration',
    'wb_sunny', 'nightlight', 'restaurant', 'fitness_center', 'weekend'
];

const colors = [
    { value: 'emerald', class: 'bg-emerald-500' },
    { value: 'orange', class: 'bg-orange-500' },
    { value: 'purple', class: 'bg-purple-500' },
    { value: 'rose', class: 'bg-rose-500' },
    { value: 'blue', class: 'bg-blue-500' },
    { value: 'cyan', class: 'bg-cyan-500' },
    { value: 'amber', class: 'bg-amber-500' },
    { value: 'pink', class: 'bg-pink-500' },
];

const colorIconClasses = {
    emerald: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600',
    orange: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600',
    purple: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600',
    rose: 'bg-rose-100 dark:bg-rose-900/30 text-rose-600',
    blue: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600',
    cyan: 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600',
    amber: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600',
    pink: 'bg-pink-100 dark:bg-pink-900/30 text-pink-600',
};

const getPreviewIconClass = computed(() => {
    return colorIconClasses[form.color] || colorIconClasses.emerald;
});

const addAction = () => {
    form.actions.push({
        device_id: '',
        command: '',
        params: {
            brightness: 100,
            temperature: 22,
            position: 100,
            color: '#FFFFFF'
        }
    });
};

const removeAction = (index) => {
    form.actions.splice(index, 1);
};

const needsParams = (command) => {
    return ['set_brightness', 'set_color', 'set_temperature', 'set_position'].includes(command);
};

const handleSubmit = () => {
    error.value = '';
    
    if (!form.name.trim()) {
        error.value = 'Senaryo adı gerekli';
        return;
    }
    
    if (form.actions.length === 0) {
        error.value = 'En az bir aksiyon ekleyin';
        return;
    }
    
    // Validate actions
    for (const action of form.actions) {
        if (!action.device_id || !action.command) {
            error.value = 'Tüm aksiyonlar için cihaz ve komut seçin';
            return;
        }
    }
    
    isLoading.value = true;
    
    emit('submit', {
        name: form.name.trim(),
        icon: form.icon,
        color: form.color,
        actions: form.actions.map(a => ({
            device_id: a.device_id,
            command: a.command,
            params: needsParams(a.command) ? a.params : {}
        }))
    });
    
    isLoading.value = false;
};
</script>
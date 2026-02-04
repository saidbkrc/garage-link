<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('close')"></div>
            
            <!-- Modal -->
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden max-h-[90vh] flex flex-col">
                
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="text-xl font-bold">{{ isEditing ? 'Odayı Düzenle' : 'Yeni Oda Ekle' }}</h3>
                        <p class="text-sm text-slate-500 mt-1">Oda bilgilerini girin</p>
                    </div>
                    <button @click="$emit('close')"
                        class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Form -->
                <div class="p-6 space-y-6 overflow-y-auto flex-1">
                    
                    <!-- Room Name -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Oda Adı</label>
                        <input v-model="form.name" type="text" required
                            class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary/50"
                            placeholder="Örn: Salon, Yatak Odası" />
                    </div>

                    <!-- Icon Picker -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">İkon</label>
                        <div class="grid grid-cols-5 gap-2">
                            <button v-for="icon in roomIcons" :key="icon.value" type="button"
                                @click="form.icon = icon.value"
                                class="flex flex-col items-center gap-1 p-3 rounded-xl transition-all"
                                :class="form.icon === icon.value 
                                    ? 'bg-primary text-white' 
                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'">
                                <span class="material-symbols-outlined text-xl">{{ icon.value }}</span>
                                <span class="text-[10px] font-medium">{{ icon.label }}</span>
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

                    <!-- Preview -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Önizleme</label>
                        <div class="p-4 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700">
                            <div class="flex items-center gap-3">
                                <div class="size-12 text-white rounded-xl flex items-center justify-center shadow-lg"
                                    :class="getPreviewIconClass">
                                    <span class="material-symbols-outlined text-2xl">{{ form.icon || 'meeting_room' }}</span>
                                </div>
                                <div>
                                    <h4 class="font-bold">{{ form.name || 'Oda Adı' }}</h4>
                                    <p class="text-xs text-slate-500">0 cihaz</p>
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
                        {{ isLoading ? 'Kaydediliyor...' : (isEditing ? 'Kaydet' : 'Oda Ekle') }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';

const props = defineProps({
    room: { type: Object, default: null },
    devices: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'submit']);

const isLoading = ref(false);
const error = ref('');

const isEditing = computed(() => !!props.room);

const form = reactive({
    name: '',
    icon: 'meeting_room',
    color: 'blue'
});

// Watch for room prop changes (edit mode)
watch(() => props.room, (newRoom) => {
    if (newRoom) {
        form.name = newRoom.name || '';
        form.icon = newRoom.icon || 'meeting_room';
        form.color = newRoom.color || 'blue';
    } else {
        form.name = '';
        form.icon = 'meeting_room';
        form.color = 'blue';
    }
}, { immediate: true });

const roomIcons = [
    { value: 'weekend', label: 'Salon' },
    { value: 'bed', label: 'Yatak' },
    { value: 'kitchen', label: 'Mutfak' },
    { value: 'bathroom', label: 'Banyo' },
    { value: 'work', label: 'Ofis' },
    { value: 'dining', label: 'Yemek' },
    { value: 'garage_home', label: 'Garaj' },
    { value: 'park', label: 'Bahçe' },
    { value: 'child_care', label: 'Çocuk' },
    { value: 'stairs', label: 'Koridor' },
];

const colors = [
    { value: 'blue', class: 'bg-blue-500' },
    { value: 'purple', class: 'bg-purple-500' },
    { value: 'orange', class: 'bg-orange-500' },
    { value: 'cyan', class: 'bg-cyan-500' },
    { value: 'emerald', class: 'bg-emerald-500' },
    { value: 'teal', class: 'bg-teal-500' },
    { value: 'amber', class: 'bg-amber-500' },
    { value: 'red', class: 'bg-red-500' },
    { value: 'pink', class: 'bg-pink-500' },
];

const colorIconClasses = {
    blue: 'bg-blue-500 shadow-blue-500/20',
    purple: 'bg-purple-500 shadow-purple-500/20',
    orange: 'bg-orange-500 shadow-orange-500/20',
    cyan: 'bg-cyan-500 shadow-cyan-500/20',
    emerald: 'bg-emerald-500 shadow-emerald-500/20',
    teal: 'bg-teal-500 shadow-teal-500/20',
    amber: 'bg-amber-500 shadow-amber-500/20',
    red: 'bg-red-500 shadow-red-500/20',
    pink: 'bg-pink-500 shadow-pink-500/20',
};

const getPreviewIconClass = computed(() => {
    return colorIconClasses[form.color] || colorIconClasses.blue;
});

const handleSubmit = () => {
    error.value = '';
    
    if (!form.name.trim()) {
        error.value = 'Oda adı gerekli';
        return;
    }
    
    isLoading.value = true;
    
    emit('submit', {
        name: form.name.trim(),
        icon: form.icon,
        color: form.color
    });
    
    isLoading.value = false;
};
</script>
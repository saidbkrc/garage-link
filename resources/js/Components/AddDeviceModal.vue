<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('close')"></div>
            
            <!-- Modal -->
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="text-xl font-bold">Yeni Cihaz Ekle</h3>
                        <p class="text-sm text-slate-500 mt-1">Sisteme yeni bir IoT cihazı ekleyin</p>
                    </div>
                    <button @click="$emit('close')"
                        class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-5">
                    
                    <!-- Device Name -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Cihaz Adı</label>
                        <input v-model="form.name" type="text" required
                            class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary/50"
                            placeholder="Örn: Salon Lambası" />
                    </div>

                    <!-- Device Type -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Cihaz Tipi</label>
                        <select v-model="form.device_type_id" required
                            class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary/50">
                            <option value="">Cihaz tipi seçin</option>
                            <option v-for="type in deviceTypes" :key="type.id" :value="type.id">
                                {{ type.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Room -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Oda</label>
                        <select v-model="form.room_id"
                            class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary/50">
                            <option value="">Oda seçin (opsiyonel)</option>
                            <option v-for="room in rooms" :key="room.id" :value="room.id">
                                {{ room.name }}
                            </option>
                        </select>
                    </div>

                    <!-- MAC Address -->
                    <!-- <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">MAC Adresi</label>
                        <input v-model="form.mac_address" type="text" required
                            class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary/50 font-mono"
                            placeholder="AA:BB:CC:DD:EE:FF" />
                        <p class="text-xs text-slate-400">Cihazın benzersiz MAC adresi</p>
                    </div> -->

                    <!-- MQTT Topic (Optional) -->
                    <!-- <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">MQTT Topic (Opsiyonel)</label>
                        <input v-model="form.mqtt_topic" type="text"
                            class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-800 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary/50 font-mono"
                            placeholder="devices/salon/lamp1" />
                    </div> -->

                    <!-- Error Message -->
                    <div v-if="error" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                        <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
                    </div>

                </form>

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
                        <span class="material-symbols-outlined text-lg" v-else>add</span>
                        {{ isLoading ? 'Ekleniyor...' : 'Cihaz Ekle' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive } from 'vue';

const props = defineProps({
    rooms: { type: Array, default: () => [] },
    deviceTypes: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'submit']);

const isLoading = ref(false);
const error = ref('');

const form = reactive({
    name: '',
    device_type_id: '',
    room_id: '',
    mac_address: '',
    mqtt_topic: ''
});

const handleSubmit = async () => {
    error.value = '';
    
    // Validation
    if (!form.name.trim()) {
        error.value = 'Cihaz adı gerekli';
        return;
    }
    if (!form.device_type_id) {
        error.value = 'Cihaz tipi seçin';
        return;
    }
    if (!form.mac_address.trim()) {
        error.value = 'MAC adresi gerekli';
        return;
    }
    
    // MAC address format validation
    const macRegex = /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/;
    if (!macRegex.test(form.mac_address)) {
        error.value = 'Geçerli bir MAC adresi girin (Örn: AA:BB:CC:DD:EE:FF)';
        return;
    }
    
    isLoading.value = true;
    
    try {
        emit('submit', {
            name: form.name.trim(),
            device_type_id: form.device_type_id,
            room_id: form.room_id || null,
            mac_address: form.mac_address.toUpperCase(),
            mqtt_topic: form.mqtt_topic.trim() || null
        });
    } catch (e) {
        error.value = 'Bir hata oluştu';
    } finally {
        isLoading.value = false;
    }
};
</script>
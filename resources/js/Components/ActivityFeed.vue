<template>
    <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-sm p-6 rounded-2xl border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg dark:text-white">Gerçek Zamanlı Aktivite</h3>
            <span class="flex items-center gap-1.5 text-xs font-bold text-green-500">
                <span class="size-2 bg-green-500 rounded-full animate-pulse"></span>
                Canlı
            </span>
        </div>

        <div class="space-y-4">
            <div v-for="(item, i) in displayActivities" :key="i"
                 class="flex gap-3 items-start pl-3 border-l-2 transition-colors"
                 :class="borderColorClass(item.type)">
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-sm dark:text-white">{{ item.title }}</p>
                    <p class="text-slate-400 text-xs mt-0.5">{{ item.message || item.description }}</p>
                    <p class="text-[10px] text-slate-500 mt-1 uppercase">{{ formatTime(item.created_at) || item.time }}</p>
                </div>
                <span class="material-symbols-outlined text-sm mt-0.5"
                      :class="iconColorClass(item.type)">
                    {{ getIcon(item.type) }}
                </span>
            </div>

            <!-- Boş durum -->
            <div v-if="displayActivities.length === 0" class="text-center py-8 text-slate-400">
                <span class="material-symbols-outlined text-3xl mb-2">notifications_off</span>
                <p class="text-sm">Henüz aktivite yok</p>
            </div>
        </div>

        <button class="w-full mt-6 text-slate-500 dark:text-slate-400 text-sm font-bold hover:text-primary transition-colors">
            Tüm Logları Gör →
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    alerts: { type: Array, default: () => [] }
});

// Eğer alerts boşsa demo veri göster
const demoActivities = [
    {
        type: 'danger',
        title: 'Gateway Bağlantı Kesildi',
        description: 'ESP32-W5500 Gateway cihazı çevrimdışı oldu',
        time: '2 dakika önce',
    },
    {
        type: 'info',
        title: 'Firmware Güncelleme',
        description: '5 cihaz v2.4.1 sürümüne güncellendi',
        time: '15 dakika önce',
    },
    {
        type: 'success',
        title: 'Senaryo Çalıştırıldı',
        description: '"Eve Geldim" senaryosu otomatik tetiklendi',
        time: '32 dakika önce',
    },
    {
        type: 'warning',
        title: 'Yüksek Enerji Uyarısı',
        description: 'Salon ışıkları 4 saattir kesintisiz açık',
        time: '1 saat önce',
    },
    {
        type: 'info',
        title: 'Yeni Cihaz Eşleşti',
        description: 'Zigbee perde motoru ağa katıldı (MAC: 0x1A2B)',
        time: '2 saat önce',
    }
];

const displayActivities = computed(() => {
    if (props.alerts && props.alerts.length > 0) {
        return props.alerts;
    }
    return demoActivities;
});

const formatTime = (dateString) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000 / 60); // dakika
    
    if (diff < 1) return 'Az önce';
    if (diff < 60) return `${diff} dakika önce`;
    if (diff < 1440) return `${Math.floor(diff / 60)} saat önce`;
    return `${Math.floor(diff / 1440)} gün önce`;
};

const getIcon = (type) => {
    const icons = {
        danger: 'error',
        warning: 'warning',
        success: 'check_circle',
        info: 'info',
    };
    return icons[type] || 'info';
};

const borderColorClass = (type) => ({
    'border-red-500': type === 'danger',
    'border-primary': type === 'info',
    'border-emerald-500': type === 'success',
    'border-amber-500': type === 'warning',
});

const iconColorClass = (type) => ({
    'text-red-500': type === 'danger',
    'text-primary': type === 'info',
    'text-emerald-500': type === 'success',
    'text-amber-500': type === 'warning',
});
</script>
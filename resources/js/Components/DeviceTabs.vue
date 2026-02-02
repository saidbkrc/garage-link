<template>
    <div>
        <!-- Sekmeler -->
        <div class="flex border-b border-slate-200 dark:border-slate-800 gap-8 overflow-x-auto">
            <button v-for="tab in tabs" :key="tab.slug" @click="activeTab = tab.slug"
                class="border-b-2 pb-4 px-2 text-sm font-bold whitespace-nowrap transition-colors"
                :class="activeTab === tab.slug
                    ? 'border-primary text-primary'
                    : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                {{ tab.label }}
                <span v-if="tab.count" class="ml-1 text-[10px] bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded-full">
                    {{ tab.count }}
                </span>
            </button>
        </div>

        <!-- Aktif Sekme İçeriği -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mt-6">
            <ClimateCard v-for="device in filteredDevices" :key="device.name" v-bind="device" />
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import ClimateCard from '@/Components/ClimateCard.vue';

const activeTab = ref('all');

const tabs = [
    { slug: 'all', label: 'Tüm Cihazlar', count: 8 },
    { slug: 'lighting', label: 'Aydınlatma', count: 3 },
    { slug: 'climate', label: 'İklimlendirme', count: 2 },
    { slug: 'curtain', label: 'Perde & Motor', count: 1 },
    { slug: 'sensor', label: 'Sensörler', count: 1 },
    { slug: 'security', label: 'Güvenlik', count: 1 },
];

const allDevices = [
    {
        name: 'Salon Klima',
        category: 'İklimlendirme',
        location: 'Salon',
        icon: 'ac_unit',
        accentColor: 'cyan',
        leftLabel: 'Sıcaklık',
        leftValue: '24°C',
        rightLabel: 'Hedef',
        rightValue: '22°C',
        barPercent: 75,
        tab: 'climate'
    },
    {
        name: 'Kombi Sistemi',
        category: 'Isıtma',
        location: 'Kazan Dairesi',
        icon: 'thermostat',
        accentColor: 'orange',
        leftLabel: 'Basınç',
        leftValue: '1.8',
        leftUnit: 'Bar',
        rightLabel: 'Su Sıcaklığı',
        rightValue: '62°C',
        barPercent: 50,
        tab: 'climate'
    },
    {
        name: 'Ambiyans Aydınlatma',
        category: 'Aydınlatma',
        location: 'Mutfak',
        icon: 'lightbulb',
        accentColor: 'purple',
        leftLabel: 'Parlaklık',
        leftValue: '80%',
        rightLabel: 'Renk',
        rightValue: 'Soft Neon',
        barPercent: 80,
        tab: 'lighting'
    },
    {
        name: 'Salon Perde',
        category: 'Perde Motor',
        location: 'Salon',
        icon: 'blinds',
        accentColor: 'emerald',
        leftLabel: 'Pozisyon',
        leftValue: '100%',
        rightLabel: 'Durum',
        rightValue: 'Açık',
        barPercent: 100,
        tab: 'curtain'
    },
    {
        name: 'Spot Işıklar',
        category: 'Aydınlatma',
        location: 'Yatak Odası',
        icon: 'fluorescent',
        accentColor: 'blue',
        leftLabel: 'Parlaklık',
        leftValue: '60%',
        rightLabel: 'Mod',
        rightValue: 'Gece',
        barPercent: 60,
        tab: 'lighting'
    },
    {
        name: 'Hareket Sensörü',
        category: 'Sensör',
        location: 'Koridor',
        icon: 'sensors',
        accentColor: 'emerald',
        leftLabel: 'Son Hareket',
        leftValue: '3 dk',
        rightLabel: 'Durum',
        rightValue: 'Aktif',
        barPercent: 90,
        tab: 'sensor'
    },
    {
        name: 'LED Şerit',
        category: 'Aydınlatma',
        location: 'Tavan Arası',
        icon: 'light',
        accentColor: 'purple',
        leftLabel: 'Parlaklık',
        leftValue: '45%',
        rightLabel: 'Efekt',
        rightValue: 'Rainbow',
        barPercent: 45,
        tab: 'lighting'
    },
    {
        name: 'Kapı Kilidi',
        category: 'Güvenlik',
        location: 'Ana Giriş',
        icon: 'lock',
        accentColor: 'red',
        leftLabel: 'Durum',
        leftValue: 'Kilitli',
        rightLabel: 'Son Açılma',
        rightValue: '14:32',
        barPercent: 100,
        active: true,
        tab: 'security'
    }
];

const filteredDevices = computed(() => {
    if (activeTab.value === 'all') return allDevices;
    return allDevices.filter(d => d.tab === activeTab.value);
});
</script>
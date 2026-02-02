<template>
    <AppLayout title="Dashboard" currentPage="Dashboard" :user="user">

        <!-- İstatistikler -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatsCard label="Toplam Cihaz" :value="stats.total_devices" icon="memory" color="blue" />
            <StatsCard label="Çevrimiçi" :value="stats.online_devices" icon="wifi" color="emerald" trend="+5.2%"
                :trendPositive="true" />
            <StatsCard label="Enerji Kullanımı" value="14.2" unit="kWh" icon="bolt" color="amber" trend="-2.1%"
                :trendPositive="false" />
            <StatsCard label="Aktif Alarmlar" :value="3" icon="warning" color="red" trend="+1 Yeni"
                :trendPositive="false" />
        </div>

        <!-- Hızlı Kontrol: Cihaz Kartları -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pt-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Hızlı Kontrol</h2>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Aydınlatma cihazlarınızı hızlıca yönetin.</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="sendGroupCommand('group_all_off')"
                    class="px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-sm transition-all">
                    Tümünü Kapat
                </button>
                <button @click="sendGroupCommand('group_all_on')"
                    class="px-5 py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-white font-semibold text-sm shadow-lg shadow-primary/20 transition-all">
                    Tümünü Aç
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <DeviceCard v-for="device in devices" :key="device.id" :device="device" :state="device.state || {}"
                @command="handleCommand" />
        </div>

        <!-- Cihaz Yönetimi (Sekmeli) -->
        <div class="pt-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold dark:text-white">Cihaz Yönetimi</h2>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">Tüm akıllı ev cihazlarınız.</p>
                </div>
                <button
                    class="px-5 py-2.5 rounded-xl bg-primary hover:bg-blue-600 text-white font-semibold text-sm flex items-center gap-2 self-start sm:self-auto transition-all">
                    <span class="material-symbols-outlined text-lg">add_circle</span>
                    Cihaz Ekle
                </button>
            </div>
            <DeviceTabs />
        </div>

        <!-- Alt Grid: Enerji + Aktivite -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pt-4">
            <div class="lg:col-span-2">
                <EnergyChart />
            </div>
            <ActivityFeed />
        </div>

        <!-- Bildirim Toast -->
        <div v-if="commandFeedback"
            class="fixed bottom-5 right-5 z-50 bg-white dark:bg-slate-800 shadow-xl rounded-xl p-4 flex items-center gap-3 border border-slate-200 dark:border-slate-700"
            style="animation: slideUp 0.3s ease-out;">
            <span class="material-symbols-outlined text-emerald-500 filled-icon">check_circle</span>
            <span class="text-sm font-medium">{{ commandFeedback }}</span>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';
import DeviceCard from '@/Components/DeviceCard.vue';
import DeviceTabs from '@/Components/DeviceTabs.vue';
import EnergyChart from '@/Components/EnergyChart.vue';
import ActivityFeed from '@/Components/ActivityFeed.vue';

const props = defineProps({
    stats: { type: Object, default: () => ({ total_devices: 0, online_devices: 0 }) },
    devices: { type: Array, default: () => [] },
    user: { type: Object, default: null }
});

const commandFeedback = ref('');

const handleCommand = async (command) => {
    try {
        const response = await fetch(`/api/v1/devices/${command.deviceId}/command`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${props.user?.token}`,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                command_slug: command.slug,
                params: command.params,
            }),
        });
        const data = await response.json();
        if (data.success) {
            commandFeedback.value = `${command.slug} komutu gönderildi`;
            setTimeout(() => commandFeedback.value = '', 2000);
        }
    } catch (error) {
        commandFeedback.value = 'Komut gönderilemedi!';
        setTimeout(() => commandFeedback.value = '', 2000);
    }
};

const sendGroupCommand = async (slug) => {
    try {
        const response = await fetch('/api/v1/commands/group', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${props.user?.token}`,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ command_slug: slug }),
        });
        const data = await response.json();
        if (data.success) {
            commandFeedback.value = `${slug} komutu gönderildi`;
            setTimeout(() => commandFeedback.value = '', 2000);
        }
    } catch (error) {
        commandFeedback.value = 'Komut gönderilemedi!';
        setTimeout(() => commandFeedback.value = '', 2000);
    }
};
</script>
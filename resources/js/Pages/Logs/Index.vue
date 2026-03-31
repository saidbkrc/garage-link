<template>
    <AppLayout title="Komut Logları" currentPage="Loglar" :user="user">

        <!-- Başlık -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Komut Logları</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Cihazlara gönderilen komutların geçmişi</p>
            </div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <span class="material-symbols-outlined text-base">history</span>
                {{ logs.total }} kayıt
            </div>
        </div>

        <!-- Filtreler -->
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <select v-model="filters.device_id" @change="applyFilters"
                class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-700 dark:text-slate-300 outline-none focus:ring-2 focus:ring-primary/30">
                <option value="">Tüm Cihazlar</option>
                <option v-for="d in devices" :key="d.id" :value="d.id">{{ d.name }}</option>
            </select>

            <select v-model="filters.status" @change="applyFilters"
                class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-700 dark:text-slate-300 outline-none focus:ring-2 focus:ring-primary/30">
                <option value="">Tüm Durumlar</option>
                <option value="pending">Bekliyor</option>
                <option value="success">Başarılı</option>
                <option value="error">Hata</option>
            </select>

            <button v-if="filters.device_id || filters.status" @click="clearFilters"
                class="flex items-center gap-1 px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-sm hover:bg-slate-200 transition-all">
                <span class="material-symbols-outlined text-base">close</span>
                Temizle
            </button>
        </div>

        <!-- Log Tablosu -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
            <div v-if="logs.data.length === 0" class="text-center py-16">
                <span class="material-symbols-outlined text-5xl text-slate-300 block mb-3">history</span>
                <p class="text-slate-500 font-medium">Henüz log kaydı yok</p>
                <p class="text-xs text-slate-400 mt-1">Cihazlara komut gönderildikçe burada görünecek</p>
            </div>

            <table v-else class="w-full text-sm">
                <thead class="border-b border-slate-200 dark:border-slate-800">
                    <tr class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                        <th class="text-left px-5 py-3">Cihaz</th>
                        <th class="text-left px-5 py-3">Komut</th>
                        <th class="text-left px-5 py-3">Parametreler</th>
                        <th class="text-left px-5 py-3">Kaynak</th>
                        <th class="text-left px-5 py-3">Durum</th>
                        <th class="text-left px-5 py-3">Kullanıcı</th>
                        <th class="text-left px-5 py-3">Zaman</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr v-for="log in logs.data" :key="log.id"
                        class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <td class="px-5 py-3 font-medium text-slate-900 dark:text-white">
                            {{ log.device_name }}
                        </td>
                        <td class="px-5 py-3">
                            <span class="font-mono text-xs bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-lg">
                                {{ log.command_slug }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-500 max-w-xs">
                            <span class="font-mono text-xs truncate block max-w-[200px]"
                                :title="JSON.stringify(log.request_payload)">
                                {{ log.request_payload ? JSON.stringify(log.request_payload).slice(0, 50) : '—' }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                :class="log.source === 'panel'
                                    ? 'bg-primary/10 text-primary'
                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-500'">
                                {{ log.source === 'panel' ? 'Panel' : 'MQTT' }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full font-bold"
                                :class="{
                                    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': log.status === 'pending',
                                    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': log.status === 'success',
                                    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': log.status === 'error',
                                }">
                                {{ { pending: 'Bekliyor', success: 'Başarılı', error: 'Hata' }[log.status] ?? log.status }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-500 text-xs">{{ log.user_name }}</td>
                        <td class="px-5 py-3 text-slate-400 text-xs">{{ formatTime(log.created_at) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Sayfalama -->
            <div v-if="logs.last_page > 1"
                class="flex items-center justify-between px-5 py-4 border-t border-slate-200 dark:border-slate-800">
                <p class="text-xs text-slate-500">
                    {{ logs.from }}–{{ logs.to }} / {{ logs.total }} kayıt
                </p>
                <div class="flex gap-1">
                    <a v-if="logs.prev_page_url" :href="logs.prev_page_url"
                        class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-xs font-medium transition-all">
                        ← Önceki
                    </a>
                    <span class="px-3 py-1.5 text-xs font-bold text-primary">
                        {{ logs.current_page }} / {{ logs.last_page }}
                    </span>
                    <a v-if="logs.next_page_url" :href="logs.next_page_url"
                        class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-xs font-medium transition-all">
                        Sonraki →
                    </a>
                </div>
            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    logs:    { type: Object, default: () => ({ data: [], total: 0 }) },
    devices: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    user:    { type: Object, default: null },
});

const filters = ref({
    device_id: props.filters.device_id ?? '',
    status:    props.filters.status ?? '',
});

const applyFilters = () => {
    router.get('/logs', {
        device_id: filters.value.device_id || undefined,
        status:    filters.value.status    || undefined,
    }, { preserveState: true, replace: true });
};

const clearFilters = () => {
    filters.value = { device_id: '', status: '' };
    router.get('/logs', {}, { replace: true });
};

const formatTime = (iso) => {
    if (!iso) return '—';
    const date = new Date(iso);
    const now  = new Date();
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60)    return 'Az önce';
    if (diff < 3600)  return `${Math.floor(diff / 60)} dk önce`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} sa önce`;
    return date.toLocaleDateString('tr-TR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
};
</script>

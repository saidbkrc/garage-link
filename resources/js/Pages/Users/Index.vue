<template>
    <AppLayout title="Kullanıcılar" currentPage="Kullanıcılar" :user="user">

        <!-- Başlık -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Kullanıcılar</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1">{{ users.length }} kullanıcı</p>
            </div>
            <button v-if="user.role === 'admin'" @click="openAdd"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-white font-semibold text-sm shadow-sm shadow-primary/20 transition-all">
                <span class="material-symbols-outlined text-base">person_add</span>
                Kullanıcı Ekle
            </button>
        </div>

        <!-- Kullanıcı Listesi -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 dark:border-slate-800">
                    <tr class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                        <th class="text-left px-6 py-3">Kullanıcı</th>
                        <th class="text-left px-6 py-3">E-posta</th>
                        <th class="text-left px-6 py-3">Rol</th>
                        <th class="text-left px-6 py-3">Durum</th>
                        <th class="text-left px-6 py-3">Katılım</th>
                        <th class="text-right px-6 py-3">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr v-for="u in users" :key="u.id"
                        class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="size-9 rounded-full flex items-center justify-center text-sm font-bold"
                                    :class="u.is_current ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'">
                                    {{ u.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        {{ u.name }}
                                        <span v-if="u.is_current" class="ml-1.5 text-xs text-primary font-normal">(Siz)</span>
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-500">{{ u.email }}</td>
                        <td class="px-6 py-4">
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full"
                                :class="{
                                    'bg-primary/10 text-primary': u.role === 'admin',
                                    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': u.role === 'technician',
                                    'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400': u.role === 'viewer',
                                }">
                                {{ { admin: 'Yönetici', technician: 'Teknisyen', viewer: 'Görüntüleyici' }[u.role] ?? u.role }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="flex items-center gap-1.5 text-xs font-medium"
                                :class="u.is_active ? 'text-green-600 dark:text-green-400' : 'text-slate-400'">
                                <span class="size-2 rounded-full"
                                    :class="u.is_active ? 'bg-green-500' : 'bg-slate-400'"></span>
                                {{ u.is_active ? 'Aktif' : 'Pasif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-400">{{ formatDate(u.created_at) }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button v-if="user.role === 'admin' && !u.is_current" @click="toggleActive(u)"
                                    class="size-8 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-all"
                                    :title="u.is_active ? 'Pasife Al' : 'Aktife Al'">
                                    <span class="material-symbols-outlined text-base">
                                        {{ u.is_active ? 'person_off' : 'person' }}
                                    </span>
                                </button>
                                <button v-if="user.role === 'admin' && !u.is_current" @click="confirmDelete(u)"
                                    class="size-8 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center justify-center text-slate-400 hover:text-red-500 transition-all">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Kullanıcı Ekleme Modalı -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-bold text-lg text-slate-900 dark:text-white">Yeni Kullanıcı Ekle</h3>
                    <button @click="showModal = false" class="size-9 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 flex items-center justify-center transition-all">
                        <span class="material-symbols-outlined text-slate-500">close</span>
                    </button>
                </div>

                <form @submit.prevent="submitAdd" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Ad Soyad</label>
                        <input v-model="form.name" type="text" placeholder="Ahmet Yılmaz" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">E-posta</label>
                        <input v-model="form.email" type="email" placeholder="ahmet@sirket.com" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Şifre</label>
                        <input v-model="form.password" type="password" placeholder="En az 8 karakter" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Rol</label>
                        <select v-model="form.role" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            <option value="admin">Yönetici</option>
                            <option value="technician">Teknisyen</option>
                            <option value="viewer">Görüntüleyici</option>
                        </select>
                    </div>

                    <div v-if="errors.length" class="px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200">
                        <p v-for="e in errors" :key="e" class="text-xs text-red-600">{{ e }}</p>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showModal = false"
                            class="flex-1 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 font-medium text-sm hover:bg-slate-50 transition-all">
                            İptal
                        </button>
                        <button type="submit" :disabled="saving"
                            class="flex-1 py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-white font-semibold text-sm disabled:opacity-50 transition-all">
                            {{ saving ? 'Ekleniyor...' : 'Ekle' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Silme Onay Modalı -->
        <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-sm p-6 text-center">
                <span class="material-symbols-outlined text-4xl text-red-500 block mb-3">person_remove</span>
                <h3 class="font-bold text-lg mb-1">Kullanıcıyı Sil</h3>
                <p class="text-sm text-slate-500 mb-6">
                    <strong>{{ deleteTarget.name }}</strong> kullanıcısı silinecek. Emin misiniz?
                </p>
                <div class="flex gap-3">
                    <button @click="deleteTarget = null" class="flex-1 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium hover:bg-slate-50 transition-all">
                        İptal
                    </button>
                    <button @click="deleteUser" :disabled="deleting" class="flex-1 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-bold disabled:opacity-50 transition-all">
                        {{ deleting ? 'Siliniyor...' : 'Evet, Sil' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <Transition name="slide-up">
            <div v-if="toast"
                class="fixed bottom-5 right-5 z-50 bg-white dark:bg-slate-800 shadow-xl rounded-xl p-4 flex items-center gap-3 border border-slate-200 dark:border-slate-700">
                <span class="material-symbols-outlined" :class="toast.type === 'success' ? 'text-emerald-500' : 'text-red-500'">
                    {{ toast.type === 'success' ? 'check_circle' : 'error' }}
                </span>
                <span class="text-sm font-medium">{{ toast.message }}</span>
            </div>
        </Transition>

    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    users: { type: Array, default: () => [] },
    user:  { type: Object, default: null },
});

const showModal   = ref(false);
const saving      = ref(false);
const errors      = ref([]);
const deleteTarget = ref(null);
const deleting    = ref(false);
const toast       = ref(null);

const form = ref({ name: '', email: '', password: '', role: 'technician' });

const showToast = (message, type = 'success') => {
    toast.value = { message, type };
    setTimeout(() => toast.value = null, 3000);
};

const openAdd = () => {
    form.value = { name: '', email: '', password: '', role: 'technician' };
    errors.value = [];
    showModal.value = true;
};

const submitAdd = () => {
    saving.value = true;
    errors.value = [];
    router.post('/users', form.value, {
        onSuccess: () => {
            showModal.value = false;
            showToast('Kullanıcı eklendi');
        },
        onError: (e) => {
            errors.value = Object.values(e).flat();
        },
        onFinish: () => saving.value = false,
    });
};

const toggleActive = (u) => {
    router.put(`/users/${u.id}`, { is_active: !u.is_active }, {
        onSuccess: () => showToast(u.is_active ? 'Kullanıcı pasife alındı' : 'Kullanıcı aktifleştirildi'),
        preserveScroll: true,
    });
};

const confirmDelete = (u) => deleteTarget.value = u;

const deleteUser = () => {
    deleting.value = true;
    router.delete(`/users/${deleteTarget.value.id}`, {
        onSuccess: () => {
            deleteTarget.value = null;
            showToast('Kullanıcı silindi');
        },
        onFinish: () => deleting.value = false,
    });
};

const formatDate = (iso) => {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('tr-TR');
};
</script>

<style>
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.3s ease; }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateY(20px); }
</style>

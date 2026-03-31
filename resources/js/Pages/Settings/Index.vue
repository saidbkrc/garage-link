<template>
    <AppLayout title="Ayarlar" currentPage="Ayarlar" :user="user">

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Ayarlar</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Profil ve güvenlik ayarlarınızı yönetin</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Bayi Bilgisi -->
            <div class="p-6 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                <div class="flex items-center gap-3 mb-5">
                    <div class="size-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined">business</span>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-white">Bayi Bilgisi</h2>
                        <p class="text-xs text-slate-500">Firma bilgileri</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-sm text-slate-500">Bayi Adı</span>
                        <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ dealer.name }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-sm text-slate-500">Kayıt Tarihi</span>
                        <span class="text-sm text-slate-600">{{ formatDate(dealer.created_at) }}</span>
                    </div>
                </div>
            </div>

            <!-- Profil Bilgileri -->
            <div class="p-6 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                <div class="flex items-center gap-3 mb-5">
                    <div class="size-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <span class="material-symbols-outlined">manage_accounts</span>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-white">Profil Bilgileri</h2>
                        <p class="text-xs text-slate-500">Ad ve e-posta</p>
                    </div>
                </div>
                <form @submit.prevent="updateProfile" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Ad Soyad</label>
                        <input v-model="profileForm.name" type="text" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">E-posta</label>
                        <input v-model="profileForm.email" type="email" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" />
                    </div>
                    <div v-if="profileErrors.length" class="px-3 py-2 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200">
                        <p v-for="e in profileErrors" :key="e" class="text-xs text-red-600">{{ e }}</p>
                    </div>
                    <button type="submit" :disabled="savingProfile"
                        class="w-full py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-white font-semibold text-sm disabled:opacity-50 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-base" :class="savingProfile ? 'animate-spin' : ''">
                            {{ savingProfile ? 'refresh' : 'save' }}
                        </span>
                        {{ savingProfile ? 'Kaydediliyor...' : 'Kaydet' }}
                    </button>
                </form>
            </div>

            <!-- Şifre Değiştir -->
            <div class="p-6 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                <div class="flex items-center gap-3 mb-5">
                    <div class="size-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <span class="material-symbols-outlined">lock</span>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-white">Şifre Değiştir</h2>
                        <p class="text-xs text-slate-500">Güvenlik için düzenli değiştirin</p>
                    </div>
                </div>
                <form @submit.prevent="updatePassword" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Mevcut Şifre</label>
                        <input v-model="passwordForm.current_password" type="password" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Yeni Şifre</label>
                        <input v-model="passwordForm.password" type="password" placeholder="En az 8 karakter" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Şifre Tekrar</label>
                        <input v-model="passwordForm.password_confirmation" type="password" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" />
                    </div>
                    <div v-if="passwordErrors.length" class="px-3 py-2 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200">
                        <p v-for="e in passwordErrors" :key="e" class="text-xs text-red-600">{{ e }}</p>
                    </div>
                    <button type="submit" :disabled="savingPassword"
                        class="w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-semibold text-sm disabled:opacity-50 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-base" :class="savingPassword ? 'animate-spin' : ''">
                            {{ savingPassword ? 'refresh' : 'lock_reset' }}
                        </span>
                        {{ savingPassword ? 'Değiştiriliyor...' : 'Şifreyi Değiştir' }}
                    </button>
                </form>
            </div>

            <!-- Sistem Bilgisi -->
            <div class="p-6 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                <div class="flex items-center gap-3 mb-5">
                    <div class="size-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 flex items-center justify-center">
                        <span class="material-symbols-outlined">info</span>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-white">Sistem Bilgisi</h2>
                        <p class="text-xs text-slate-500">Versiyon ve bağlantı detayları</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-sm text-slate-500">Platform</span>
                        <span class="text-sm font-semibold text-slate-900 dark:text-white">PigaSoft IoT v1.0</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-sm text-slate-500">MQTT Broker</span>
                        <span class="font-mono text-xs bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-lg text-slate-600 dark:text-slate-400">broker.hivemq.com:1883</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-sm text-slate-500">MQTT Topic Prefix</span>
                        <span class="font-mono text-xs bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-lg text-slate-600 dark:text-slate-400">pigasoft/</span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-sm text-slate-500">Rolünüz</span>
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full"
                            :class="{
                                'bg-primary/10 text-primary': user.role === 'admin',
                                'bg-blue-100 text-blue-700': user.role === 'technician',
                                'bg-slate-100 text-slate-600': user.role === 'viewer',
                            }">
                            {{ { admin: 'Yönetici', technician: 'Teknisyen', viewer: 'Görüntüleyici' }[user.role] }}
                        </span>
                    </div>
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
    dealer: { type: Object, default: () => ({}) },
    user:   { type: Object, default: null },
});

const profileForm = ref({ name: props.user?.name ?? '', email: props.user?.email ?? '' });
const passwordForm = ref({ current_password: '', password: '', password_confirmation: '' });

const savingProfile = ref(false);
const savingPassword = ref(false);
const profileErrors = ref([]);
const passwordErrors = ref([]);
const toast = ref(null);

const showToast = (message, type = 'success') => {
    toast.value = { message, type };
    setTimeout(() => toast.value = null, 3000);
};

const updateProfile = () => {
    savingProfile.value = true;
    profileErrors.value = [];
    router.put('/settings/profile', profileForm.value, {
        onSuccess: () => showToast('Profil güncellendi'),
        onError: (e) => profileErrors.value = Object.values(e).flat(),
        onFinish: () => savingProfile.value = false,
    });
};

const updatePassword = () => {
    savingPassword.value = true;
    passwordErrors.value = [];
    router.put('/settings/password', passwordForm.value, {
        onSuccess: () => {
            passwordForm.value = { current_password: '', password: '', password_confirmation: '' };
            showToast('Şifre başarıyla değiştirildi');
        },
        onError: (e) => passwordErrors.value = Object.values(e).flat(),
        onFinish: () => savingPassword.value = false,
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

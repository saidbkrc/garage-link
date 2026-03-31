<template>
    <AppLayout title="Zamanlamalar" currentPage="Zamanlamalar" :user="user">

        <!-- Başlık -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Zamanlamalar</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1">
                    {{ schedules.length }} zamanlama
                    <span class="text-xs text-slate-400 ml-2">— Otomatik yürütme için
                        <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">php artisan schedule:run</code>
                        gereklidir
                    </span>
                </p>
            </div>
            <button @click="openCreate"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-white font-semibold text-sm shadow-sm shadow-primary/20 transition-all">
                <span class="material-symbols-outlined text-base">add_alarm</span>
                Zamanlama Ekle
            </button>
        </div>

        <!-- Boş durum -->
        <div v-if="schedules.length === 0"
            class="p-12 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 text-center">
            <span class="material-symbols-outlined text-5xl text-slate-400 block mb-3">alarm</span>
            <p class="font-bold text-slate-600 dark:text-slate-400">Henüz zamanlama yok</p>
            <p class="text-sm text-slate-500 mt-1">Otomatik çalışacak komutlar ve senaryolar ekleyin</p>
            <button @click="openCreate" class="mt-4 px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm">
                İlk Zamanlamayı Oluştur
            </button>
        </div>

        <!-- Zamanlama Listesi -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div v-for="s in schedules" :key="s.id"
                class="p-5 rounded-2xl border transition-all"
                :class="s.is_active
                    ? 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-sm'
                    : 'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 opacity-60'">

                <!-- Üst satır: saat + toggle -->
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="size-10 rounded-xl flex items-center justify-center"
                            :class="s.is_active ? 'bg-primary/10 text-primary' : 'bg-slate-200 dark:bg-slate-700 text-slate-400'">
                            <span class="material-symbols-outlined">alarm</span>
                        </div>
                        <div>
                            <p class="text-xl font-bold tabular-nums text-slate-900 dark:text-white">{{ s.time }}</p>
                            <p class="font-semibold text-sm text-slate-700 dark:text-slate-300">{{ s.name }}</p>
                        </div>
                    </div>
                    <!-- Toggle aktif/pasif -->
                    <button @click="toggleActive(s)"
                        class="relative w-11 h-6 rounded-full transition-colors focus:outline-none"
                        :class="s.is_active ? 'bg-primary' : 'bg-slate-300 dark:bg-slate-600'">
                        <span class="absolute top-0.5 size-5 bg-white rounded-full transition-all shadow"
                            :class="s.is_active ? 'left-5' : 'left-0.5'"></span>
                    </button>
                </div>

                <!-- Günler -->
                <div class="flex flex-wrap gap-1 mb-3">
                    <span v-for="day in allDays" :key="day.value"
                        class="text-xs px-2 py-0.5 rounded-full font-medium"
                        :class="s.days?.includes(day.value)
                            ? 'bg-primary/10 text-primary'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-400'">
                        {{ day.short }}
                    </span>
                </div>

                <!-- Hedef (cihaz veya senaryo) + komut -->
                <div class="text-xs text-slate-500 space-y-1 mb-4">
                    <p v-if="s.scene" class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-base text-purple-500">play_circle</span>
                        Senaryo: <strong class="text-slate-700 dark:text-slate-300">{{ s.scene.name }}</strong>
                    </p>
                    <p v-else-if="s.device" class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-base text-primary">memory</span>
                        {{ s.device.name }}
                        <span class="bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded font-mono ml-1">{{ s.command_slug }}</span>
                    </p>
                    <p v-if="s.last_triggered_at" class="text-slate-400">
                        Son çalışma: {{ formatTime(s.last_triggered_at) }}
                    </p>
                </div>

                <!-- Aksiyon butonları -->
                <div class="flex items-center gap-2">
                    <button @click="openEdit(s)"
                        class="flex-1 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-base">edit</span>
                        Düzenle
                    </button>
                    <button @click="confirmDelete(s)"
                        class="size-8 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center justify-center text-slate-400 hover:text-red-500 transition-all">
                        <span class="material-symbols-outlined text-base">delete</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Oluştur / Düzenle Modalı -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800 sticky top-0 bg-white dark:bg-slate-900 rounded-t-3xl">
                    <h3 class="font-bold text-lg text-slate-900 dark:text-white">
                        {{ editTarget ? 'Zamanlamayı Düzenle' : 'Yeni Zamanlama' }}
                    </h3>
                    <button @click="showModal = false" class="size-9 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 flex items-center justify-center">
                        <span class="material-symbols-outlined text-slate-500">close</span>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="p-6 space-y-5">
                    <!-- Ad -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Zamanlama Adı</label>
                        <input v-model="form.name" type="text" placeholder="Sabah uyandırma" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" />
                    </div>

                    <!-- Saat -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Çalışma Saati</label>
                        <input v-model="form.time" type="time" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" />
                    </div>

                    <!-- Günler -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-2">Günler</label>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="day in allDays" :key="day.value" type="button"
                                @click="toggleDay(day.value)"
                                class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all"
                                :class="form.days.includes(day.value)
                                    ? 'bg-primary text-white'
                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'">
                                {{ day.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Hedef: Senaryo veya Cihaz -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Hedef Türü</label>
                        <div class="flex gap-2 mb-3">
                            <button type="button" @click="form.target_type = 'device'; form.scene_id = null"
                                class="flex-1 py-2 rounded-xl text-sm font-medium transition-all"
                                :class="form.target_type === 'device' ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                                Cihaz
                            </button>
                            <button type="button" @click="form.target_type = 'scene'; form.device_id = null; form.command_slug = ''"
                                class="flex-1 py-2 rounded-xl text-sm font-medium transition-all"
                                :class="form.target_type === 'scene' ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                                Senaryo
                            </button>
                        </div>

                        <!-- Cihaz seç -->
                        <div v-if="form.target_type === 'device'" class="space-y-3">
                            <select v-model="form.device_id" required
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                                <option :value="null">— Cihaz seçin —</option>
                                <option v-for="d in devices" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                            <select v-model="form.command_slug" required
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                                <option value="">— Komut seçin —</option>
                                <option v-for="c in commands" :key="c.slug" :value="c.slug">{{ c.name }}</option>
                            </select>
                            <!-- Brightness param -->
                            <div v-if="form.command_slug === 'brightness'" class="flex items-center gap-3">
                                <label class="text-xs text-slate-500 w-24">Parlaklık:</label>
                                <input v-model.number="form.params.brightness" type="range" min="0" max="100" class="flex-1" />
                                <span class="text-sm font-bold w-8 text-right text-primary">{{ form.params.brightness ?? 50 }}</span>
                            </div>
                        </div>

                        <!-- Senaryo seç -->
                        <div v-if="form.target_type === 'scene'">
                            <select v-model="form.scene_id" required
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                                <option :value="null">— Senaryo seçin —</option>
                                <option v-for="sc in scenes" :key="sc.id" :value="sc.id">{{ sc.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Hata -->
                    <div v-if="formErrors.length" class="px-3 py-2 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200">
                        <p v-for="e in formErrors" :key="e" class="text-xs text-red-600">{{ e }}</p>
                    </div>

                    <!-- Butonlar -->
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showModal = false"
                            class="flex-1 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium hover:bg-slate-50 transition-all">
                            İptal
                        </button>
                        <button type="submit" :disabled="saving"
                            class="flex-1 py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-white font-semibold text-sm disabled:opacity-50 transition-all">
                            {{ saving ? 'Kaydediliyor...' : (editTarget ? 'Güncelle' : 'Oluştur') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Silme onayı -->
        <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-sm p-6 text-center">
                <span class="material-symbols-outlined text-4xl text-red-500 block mb-3">delete_forever</span>
                <h3 class="font-bold text-lg mb-1">Zamanlamayı Sil</h3>
                <p class="text-sm text-slate-500 mb-6"><strong>{{ deleteTarget.name }}</strong> silinecek.</p>
                <div class="flex gap-3">
                    <button @click="deleteTarget = null" class="flex-1 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium">İptal</button>
                    <button @click="deleteSchedule" :disabled="deleting" class="flex-1 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-bold disabled:opacity-50">
                        {{ deleting ? 'Siliniyor...' : 'Sil' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <Transition name="slide-up">
            <div v-if="toast"
                class="fixed bottom-5 right-5 z-50 bg-white dark:bg-slate-800 shadow-xl rounded-xl p-4 flex items-center gap-3 border border-slate-200 dark:border-slate-700">
                <span class="material-symbols-outlined text-emerald-500">check_circle</span>
                <span class="text-sm font-medium">{{ toast }}</span>
            </div>
        </Transition>

    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    schedules: { type: Array, default: () => [] },
    devices:   { type: Array, default: () => [] },
    scenes:    { type: Array, default: () => [] },
    commands:  { type: Array, default: () => [] },
    user:      { type: Object, default: null },
});

const allDays = [
    { value: 'monday',    label: 'Pzt', short: 'Pt' },
    { value: 'tuesday',   label: 'Sal', short: 'Sa' },
    { value: 'wednesday', label: 'Çar', short: 'Ça' },
    { value: 'thursday',  label: 'Per', short: 'Pe' },
    { value: 'friday',    label: 'Cum', short: 'Cu' },
    { value: 'saturday',  label: 'Cmt', short: 'Ct' },
    { value: 'sunday',    label: 'Paz', short: 'Pz' },
];

const showModal   = ref(false);
const editTarget  = ref(null);
const deleteTarget = ref(null);
const saving      = ref(false);
const deleting    = ref(false);
const toast       = ref(null);
const formErrors  = ref([]);

const defaultForm = () => ({
    name:         '',
    time:         '08:00',
    days:         ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
    target_type:  'device',
    device_id:    null,
    scene_id:     null,
    command_slug: '',
    params:       { brightness: 50 },
});

const form = ref(defaultForm());

const showToast = (msg) => {
    toast.value = msg;
    setTimeout(() => toast.value = null, 3000);
};

const toggleDay = (day) => {
    const idx = form.value.days.indexOf(day);
    if (idx >= 0) form.value.days.splice(idx, 1);
    else form.value.days.push(day);
};

const openCreate = () => {
    editTarget.value = null;
    form.value = defaultForm();
    formErrors.value = [];
    showModal.value = true;
};

const openEdit = (s) => {
    editTarget.value = s;
    form.value = {
        name:         s.name,
        time:         s.time,
        days:         [...(s.days ?? [])],
        target_type:  s.scene ? 'scene' : 'device',
        device_id:    s.device?.id ?? null,
        scene_id:     s.scene?.id ?? null,
        command_slug: s.command_slug ?? '',
        params:       { ...(s.params ?? { brightness: 50 }) },
    };
    formErrors.value = [];
    showModal.value = true;
};

const submitForm = () => {
    if (form.value.days.length === 0) {
        formErrors.value = ['En az bir gün seçiniz'];
        return;
    }
    saving.value = true;
    formErrors.value = [];

    const payload = {
        name:         form.value.name,
        time:         form.value.time,
        days:         form.value.days,
        device_id:    form.value.target_type === 'device' ? form.value.device_id : null,
        scene_id:     form.value.target_type === 'scene'  ? form.value.scene_id  : null,
        command_slug: form.value.target_type === 'device' ? form.value.command_slug : null,
        params:       form.value.target_type === 'device' ? form.value.params : {},
    };

    const url = editTarget.value ? `/schedules/${editTarget.value.id}` : '/schedules';
    const method = editTarget.value ? 'put' : 'post';

    router[method](url, payload, {
        onSuccess: () => {
            showModal.value = false;
            showToast(editTarget.value ? 'Zamanlama güncellendi' : 'Zamanlama oluşturuldu');
        },
        onError: (e) => { formErrors.value = Object.values(e).flat(); },
        onFinish: () => saving.value = false,
    });
};

const toggleActive = (s) => {
    router.put(`/schedules/${s.id}`, { is_active: !s.is_active }, {
        onSuccess: () => showToast(s.is_active ? 'Zamanlama durduruldu' : 'Zamanlama etkinleştirildi'),
        preserveScroll: true,
    });
};

const confirmDelete = (s) => deleteTarget.value = s;

const deleteSchedule = () => {
    deleting.value = true;
    router.delete(`/schedules/${deleteTarget.value.id}`, {
        onSuccess: () => {
            deleteTarget.value = null;
            showToast('Zamanlama silindi');
        },
        onFinish: () => deleting.value = false,
    });
};

const formatTime = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    const now = new Date();
    const diff = Math.floor((now - d) / 1000);
    if (diff < 3600)  return `${Math.floor(diff / 60)} dk önce`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} sa önce`;
    return d.toLocaleDateString('tr-TR');
};
</script>

<style>
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.3s ease; }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateY(20px); }
</style>

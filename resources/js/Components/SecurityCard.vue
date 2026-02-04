<template>
    <div class="group relative overflow-hidden p-6 rounded-[2rem] border transition-all bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md hover:shadow-xl">

        <!-- Durum Badge - Sağ Üst -->
        <div class="absolute top-4 right-4 px-3 py-1 rounded-full text-[10px] font-bold"
            :class="isSecure 
                ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600' 
                : 'bg-red-100 dark:bg-red-900/30 text-red-600'">
            {{ isSecure ? 'GÜVENLİ' : 'AÇIK' }}
        </div>

        <!-- Üst: İkon -->
        <div class="flex justify-between items-start relative z-10">
            <div class="size-14 rounded-2xl flex items-center justify-center transition-all"
                :class="isSecure 
                    ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-500' 
                    : 'bg-red-100 dark:bg-red-900/40 text-red-500 animate-pulse'">
                <span class="material-symbols-outlined text-3xl filled-icon">
                    {{ securityConfig[deviceType]?.icon || 'lock' }}
                </span>
            </div>
        </div>

        <!-- İsim + Konum -->
        <div class="mt-6">
            <div class="flex items-center gap-2">
                <h3 class="font-bold text-lg">{{ name }}</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="securityConfig[deviceType]?.badge || 'bg-slate-100 dark:bg-slate-800 text-slate-600'">
                    {{ securityConfig[deviceType]?.label || 'GÜVENLİK' }}
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ location }}</p>
        </div>

        <!-- Durum Gösterimi -->
        <div class="mt-4 p-4 rounded-2xl"
            :class="isSecure ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-red-50 dark:bg-red-900/20'">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-2xl"
                    :class="isSecure ? 'text-emerald-500' : 'text-red-500'">
                    {{ isSecure ? 'verified_user' : 'gpp_maybe' }}
                </span>
                <div>
                    <p class="font-bold" :class="isSecure ? 'text-emerald-600' : 'text-red-600'">
                        {{ statusText }}
                    </p>
                    <p class="text-xs text-slate-400">Son işlem: {{ lastActivity }}</p>
                </div>
            </div>
        </div>

        <!-- Kontrol Butonları -->
        <div class="mt-4 grid grid-cols-2 gap-2">
            <button @click="toggleSecurity(true)"
                class="py-3 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2"
                :class="isSecure 
                    ? 'bg-emerald-500 text-white' 
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30'">
                <span class="material-symbols-outlined text-lg">{{ securityConfig[deviceType]?.lockIcon || 'lock' }}</span>
                {{ securityConfig[deviceType]?.lockText || 'Kilitle' }}
            </button>
            <button @click="toggleSecurity(false)"
                class="py-3 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2"
                :class="!isSecure 
                    ? 'bg-red-500 text-white' 
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-red-100 dark:hover:bg-red-900/30'">
                <span class="material-symbols-outlined text-lg">{{ securityConfig[deviceType]?.unlockIcon || 'lock_open' }}</span>
                {{ securityConfig[deviceType]?.unlockText || 'Aç' }}
            </button>
        </div>

        <!-- Son Aktiviteler -->
        <div class="mt-4 space-y-2">
            <p class="text-xs font-bold text-slate-400">Son Aktiviteler</p>
            <div v-for="(activity, i) in recentActivities" :key="i" 
                class="flex items-center gap-2 text-xs text-slate-500">
                <span class="size-1.5 rounded-full" :class="activity.secure ? 'bg-emerald-500' : 'bg-red-500'"></span>
                <span class="flex-1">{{ activity.text }}</span>
                <span class="text-slate-400">{{ activity.time }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    name: { type: String, default: 'Kapı Kilidi' },
    location: { type: String, default: 'Giriş' },
    deviceType: { type: String, default: 'lock' }, // lock, garage, alarm, camera
    isLocked: { type: Boolean, default: true },
    lastActivity: { type: String, default: '14:32' }
});

const emit = defineEmits(['command']);

const isSecure = ref(props.isLocked);

const securityConfig = {
    lock: {
        icon: 'lock',
        label: 'KİLİT',
        badge: 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600',
        lockIcon: 'lock',
        lockText: 'Kilitle',
        unlockIcon: 'lock_open',
        unlockText: 'Aç'
    },
    garage: {
        icon: 'garage',
        label: 'GARAJ',
        badge: 'bg-slate-200 dark:bg-slate-700 text-slate-600',
        lockIcon: 'garage',
        lockText: 'Kapat',
        unlockIcon: 'garage_home',
        unlockText: 'Aç'
    },
    alarm: {
        icon: 'crisis_alert',
        label: 'ALARM',
        badge: 'bg-red-100 dark:bg-red-900/30 text-red-600',
        lockIcon: 'shield',
        lockText: 'Aktif Et',
        unlockIcon: 'shield_locked',
        unlockText: 'Devre Dışı'
    },
    camera: {
        icon: 'videocam',
        label: 'KAMERA',
        badge: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600',
        lockIcon: 'videocam',
        lockText: 'Kaydet',
        unlockIcon: 'videocam_off',
        unlockText: 'Durdur'
    }
};

const statusText = computed(() => {
    if (props.deviceType === 'lock') return isSecure.value ? 'Kapı Kilitli' : 'Kapı Açık';
    if (props.deviceType === 'garage') return isSecure.value ? 'Garaj Kapalı' : 'Garaj Açık';
    if (props.deviceType === 'alarm') return isSecure.value ? 'Alarm Aktif' : 'Alarm Devre Dışı';
    if (props.deviceType === 'camera') return isSecure.value ? 'Kayıt Yapılıyor' : 'Kayıt Durduruldu';
    return isSecure.value ? 'Güvenli' : 'Açık';
});

const recentActivities = [
    { text: 'Mobil uygulama ile açıldı', time: '14:32', secure: false },
    { text: 'Otomatik kilitlendi', time: '14:35', secure: true },
    { text: 'Parmak izi ile açıldı', time: '09:15', secure: false },
];

const toggleSecurity = (secure) => {
    isSecure.value = secure;
    emit('command', { type: 'security', value: secure });
};
</script>
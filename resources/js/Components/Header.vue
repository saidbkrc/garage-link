<template>
    <header
        class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md sticky top-0 z-40 px-8 flex items-center justify-between">
        
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm">
            <a class="text-slate-500 hover:text-primary transition-colors" href="/">Panel</a>
            <span class="text-slate-300 dark:text-slate-600">/</span>
            <span class="font-medium text-slate-900 dark:text-white">{{ title }}</span>
        </div>

        <!-- Right Side -->
        <div class="flex items-center gap-4">
            
            <!-- Notifications -->
            <button
                class="size-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors relative">
                <span class="material-symbols-outlined">notifications</span>
                <span v-if="notificationCount > 0"
                    class="absolute top-2 right-2 size-2.5 bg-red-500 rounded-full border-2 border-white dark:border-slate-800"></span>
            </button>

            <!-- User Dropdown -->
            <div class="relative" ref="dropdownRef">
                <button @click="isOpen = !isOpen"
                    class="h-10 px-3 flex items-center gap-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer transition-colors"
                    :class="{ 'bg-slate-100 dark:bg-slate-800': isOpen }">
                    <div class="text-right">
                        <p class="text-xs font-bold leading-none">{{ user?.name }}</p>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-wider">
                            {{ user?.dealer_name }}
                        </p>
                    </div>
                    <div class="size-8 rounded-full bg-primary/10 flex items-center justify-center">
                        <span class="text-sm font-semibold text-primary">{{ user?.name?.charAt(0) }}</span>
                    </div>
                    <span class="material-symbols-outlined text-slate-400 text-lg transition-transform duration-200"
                        :class="{ 'rotate-180': isOpen }">
                        expand_more
                    </span>
                </button>

                <!-- Dropdown Menu -->
                <Transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="opacity-0 scale-95 -translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-100 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 -translate-y-2"
                >
                    <div v-if="isOpen"
                        class="absolute right-0 top-full mt-2 w-64 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden z-50">
                        
                        <!-- User Info Header -->
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="size-12 rounded-full bg-primary/10 flex items-center justify-center">
                                    <span class="text-lg font-bold text-primary">{{ user?.name?.charAt(0) }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-sm text-slate-900 dark:text-white truncate">{{ user?.name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ user?.email }}</p>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold mt-1"
                                        :class="user?.role === 'admin' 
                                            ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-600' 
                                            : 'bg-slate-100 dark:bg-slate-700 text-slate-600'">
                                        {{ user?.role === 'admin' ? 'Admin' : 'Personel' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Menu Items -->
                        <div class="py-2">
                            <a href="/profile"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                <span class="material-symbols-outlined text-lg text-slate-400">person</span>
                                Profilim
                            </a>
                            <a href="/settings"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                <span class="material-symbols-outlined text-lg text-slate-400">settings</span>
                                Ayarlar
                            </a>
                            <a href="/help"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                <span class="material-symbols-outlined text-lg text-slate-400">help</span>
                                Yardım & Destek
                            </a>
                        </div>

                        <!-- Logout -->
                        <div class="border-t border-slate-200 dark:border-slate-800 py-2">
                            <button @click="handleLogout"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors w-full">
                                <span class="material-symbols-outlined text-lg">logout</span>
                                Çıkış Yap
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    title: { type: String, default: 'Dashboard' },
    user: { type: Object, default: null },
    notificationCount: { type: Number, default: 0 }
});

const isOpen = ref(false);
const dropdownRef = ref(null);

// Click outside handler
const handleClickOutside = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});

const handleLogout = () => {
    router.post('/logout');
};
</script>
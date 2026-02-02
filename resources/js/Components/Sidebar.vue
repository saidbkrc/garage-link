<template>
    <aside
        class="w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col fixed h-full z-50">
        <div class="p-6">
            <div class="flex items-center gap-3">
                <div class="size-10 bg-primary rounded-xl flex items-center justify-center text-white">
                    <span class="material-symbols-outlined filled-icon">hub</span>
                </div>
                <div>
                    <h1 class="text-lg font-bold leading-none">PigaSoft</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">IoT Dealer Panel</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 px-4 space-y-1">
            <a v-for="item in menuItems" :key="item.slug" :href="item.href"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors" :class="currentPage === item.slug
                    ? 'bg-primary/10 text-primary font-medium'
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
                <span class="material-symbols-outlined" :class="currentPage === item.slug ? 'filled-icon' : ''">
                    {{ item.icon }}
                </span>
                <span>{{ item.label }}</span>
            </a>
        </nav>

        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            <button @click="toggleDark"
                class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined">dark_mode</span>
                    <span>Dark Mode</span>
                </div>
                <div class="w-8 h-4 bg-slate-300 dark:bg-primary rounded-full relative transition-colors">
                    <div class="absolute top-0.5 size-3 bg-white rounded-full transition-all"
                        :class="isDark ? 'left-1' : 'right-1'"></div>
                </div>
            </button>
        </div>
    </aside>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    currentPage: { type: String, default: 'Dashboard' }
});

const menuItems = [
    { slug: 'Dashboard', label: 'Dashboard', icon: 'dashboard', href: '/panel' },
    { slug: 'Devices', label: 'Cihazlar', icon: 'devices', href: '/panel/devices' },
    { slug: 'Commands', label: 'Komutlar', icon: 'terminal', href: '/panel/commands' },
    { slug: 'Logs', label: 'Loglar', icon: 'description', href: '/panel/logs' },
    { slug: 'Users', label: 'Kullanıcılar', icon: 'group', href: '/panel/users' },
];

const isDark = ref(document.documentElement.classList.contains('dark'));

const toggleDark = () => {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('dark');
};
</script>
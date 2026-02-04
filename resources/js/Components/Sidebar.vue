<template>
    <aside class="w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col fixed h-full z-30">
        <div class="p-6 flex flex-col h-full">
            
            <!-- Logo -->
            <div class="flex items-center gap-3 mb-10">
                <div class="bg-primary rounded-lg p-2 text-white">
                    <span class="material-symbols-outlined">hub</span>
                </div>
                <div>
                    <h1 class="text-lg font-bold leading-tight">PigaSoft</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">IoT Dealer Portal</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-1">
                <a href="/"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors"
                    :class="isActive('Dashboard') 
                        ? 'bg-primary/10 text-primary font-bold' 
                        : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'">
                    <span class="material-symbols-outlined" :class="isActive('Dashboard') ? 'fill-icon' : ''">dashboard</span>
                    <span class="text-sm">Dashboard</span>
                </a>

                <a href="/devices"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors"
                    :class="isActive('Cihazlar') 
                        ? 'bg-primary/10 text-primary font-bold' 
                        : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'">
                    <span class="material-symbols-outlined" :class="isActive('Cihazlar') ? 'fill-icon' : ''">memory</span>
                    <span class="text-sm">Cihazlar</span>
                </a>

                <a href="/rooms"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors"
                    :class="isActive('Odalar') 
                        ? 'bg-primary/10 text-primary font-bold' 
                        : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'">
                    <span class="material-symbols-outlined" :class="isActive('Odalar') ? 'fill-icon' : ''">meeting_room</span>
                    <span class="text-sm">Odalar</span>
                </a>

                <a href="/scenes"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors"
                    :class="isActive('Senaryolar') 
                        ? 'bg-primary/10 text-primary font-bold' 
                        : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'">
                    <span class="material-symbols-outlined" :class="isActive('Senaryolar') ? 'fill-icon' : ''">play_circle</span>
                    <span class="text-sm">Senaryolar</span>
                </a>

                <a href="/logs"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors"
                    :class="isActive('Loglar') 
                        ? 'bg-primary/10 text-primary font-bold' 
                        : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'">
                    <span class="material-symbols-outlined" :class="isActive('Loglar') ? 'fill-icon' : ''">history</span>
                    <span class="text-sm">Loglar</span>
                </a>

                <!-- Separator -->
                <div class="my-4 border-t border-slate-200 dark:border-slate-800"></div>
                <p class="px-3 text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Ayarlar</p>

                <a href="/users"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors"
                    :class="isActive('Kullanıcılar') 
                        ? 'bg-primary/10 text-primary font-bold' 
                        : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'">
                    <span class="material-symbols-outlined" :class="isActive('Kullanıcılar') ? 'fill-icon' : ''">group</span>
                    <span class="text-sm">Kullanıcılar</span>
                </a>

                <a href="/settings"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors"
                    :class="isActive('Ayarlar') 
                        ? 'bg-primary/10 text-primary font-bold' 
                        : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'">
                    <span class="material-symbols-outlined" :class="isActive('Ayarlar') ? 'fill-icon' : ''">settings</span>
                    <span class="text-sm">Ayarlar</span>
                </a>
            </nav>

            <!-- Dark Mode Toggle -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                <button @click="toggleDarkMode"
                    class="flex items-center justify-between w-full px-3 py-2.5 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined">{{ isDark ? 'light_mode' : 'dark_mode' }}</span>
                        <span class="text-sm">{{ isDark ? 'Açık Tema' : 'Koyu Tema' }}</span>
                    </div>
                    <div class="w-10 h-5 rounded-full relative transition-colors"
                        :class="isDark ? 'bg-primary' : 'bg-slate-300'">
                        <div class="absolute top-0.5 size-4 bg-white rounded-full transition-all"
                            :class="isDark ? 'left-5' : 'left-0.5'"></div>
                    </div>
                </button>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    currentPage: { type: String, default: 'Dashboard' }
});

const isDark = ref(false);

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
});

const isActive = (page) => {
    return props.currentPage === page;
};

const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('dark', isDark.value);
    localStorage.setItem('darkMode', isDark.value ? 'true' : 'false');
};
</script>

<style>
.fill-icon {
    font-variation-settings: 'FILL' 1;
}
</style>
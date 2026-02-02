<template>
    <header
        class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md sticky top-0 z-40 px-8 flex items-center justify-between">
        <div class="flex items-center gap-2 text-sm">
            <a class="text-slate-500 hover:text-primary transition-colors" href="/panel">Panel</a>
            <span class="text-slate-300 dark:text-slate-600">/</span>
            <span class="font-medium text-slate-900 dark:text-white">{{ title }}</span>
        </div>
        <div class="flex items-center gap-4">
            <button
                class="size-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 relative">
                <span class="material-symbols-outlined">notifications</span>
                <span
                    class="absolute top-2.5 right-2.5 size-2 bg-red-500 rounded-full border-2 border-white dark:border-slate-800"></span>
            </button>
            <div
                class="h-10 px-3 flex items-center gap-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer transition-colors">
                <div class="text-right">
                    <p class="text-xs font-bold leading-none">{{ user?.name }}</p>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-wider">{{
                        user?.dealer_name }}</p>
                </div>
                <div class="size-8 rounded-full bg-primary/10 flex items-center justify-center">
                    <span class="text-sm font-semibold text-primary">{{ user?.name?.charAt(0) }}</span>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
defineProps({
    title: { type: String, default: 'Dashboard' },
    user: { type: Object, default: null }
});
</script>
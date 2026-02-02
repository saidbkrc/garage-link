<template>
    <div class="flex min-h-screen">
        <Sidebar :currentPage="currentPage" />

        <main class="flex-1 ml-64 flex flex-col">
            <Header :title="title" :user="user" />

            <div class="p-8 space-y-8 flex-1">
                <slot />
            </div>

            <Footer />
        </main>
    </div>
</template>

<script setup>
import Sidebar from '@/Components/Sidebar.vue';
import Header from '@/Components/Header.vue';
import Footer from '@/Components/Footer.vue';

defineProps({
    title: { type: String, default: 'Dashboard' },
    currentPage: { type: String, default: 'Dashboard' },
    user: { type: Object, default: null }
});
</script>
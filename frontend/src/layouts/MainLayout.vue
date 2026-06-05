<template>
    <div class="flex h-screen bg-gray-50 overflow-hidden">
        <!-- Sidebar Backdrop for mobile -->
        <div v-if="isSidebarOpen" @click="isSidebarOpen = false" class="fixed inset-0 bg-black/50 z-40 lg:hidden"></div>

        <!-- Sidebar -->
        <div :class="[
            'fixed inset-y-0 left-0 z-50 transform transition-transform duration-300 ease-in-out lg:relative lg:translate-x-0',
            isSidebarOpen ? 'translate-x-0' : '-translate-x-full'
        ]">
            <Sidebar @close="isSidebarOpen = false" />
        </div>

        <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
            <Header @toggle-sidebar="isSidebarOpen = !isSidebarOpen" />

            <main class="flex-1 overflow-auto relative">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import Sidebar from './Sidebar.vue';
import Header from './Header.vue';

const isSidebarOpen = ref(false);
const route = useRoute();

// Close sidebar on route change on mobile
watch(route, () => {
    isSidebarOpen.value = false;
});
</script>
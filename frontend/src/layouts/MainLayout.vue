<template>
    <div class="flex h-screen bg-gray-50">
        <Sidebar />

        <div class="flex-1 flex flex-col min-w-0">
            <Header />

            <main class="flex-1 overflow-auto">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import Sidebar from './Sidebar.vue';
import Header from './Header.vue';
</script>
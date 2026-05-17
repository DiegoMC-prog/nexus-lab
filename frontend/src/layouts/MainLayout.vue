<template>
    <div class="flex h-screen bg-gray-50">
        <Sidebar />

        <div class="flex-1 ml-64 flex flex-col">
            <Header />

            <main class="flex-1 overflow-auto">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import Sidebar from './SideBar.vue';
import Header from './Header.vue';
</script>
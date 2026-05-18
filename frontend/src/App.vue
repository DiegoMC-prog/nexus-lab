<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { RouterView } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useErrorStore } from '@/stores/errorStore';
import ConnectionError from '@/components/ConnectionError.vue';
import api from '@/services/api';
import type { User } from './types/auth';

const authStore = useAuthStore();
const errorStore = useErrorStore();

const isBooting = ref(true);

onMounted(async () => {
  if (authStore.isAuthenticated) {
    try {
      const response = await api.get<User>('/user');
      authStore.refreshUser(response.data);

    } catch (error) {
      console.log("Conexión fallida en el arranque.");
    }
  }
  isBooting.value = false;
});
</script>

<template>
  <div v-if="isBooting || errorStore.isChecking" class="min-h-screen bg-gray-900 flex items-center justify-center">
    <div class="animate-spin inline-block w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full"></div>
  </div>

  <template v-else>
    <ConnectionError v-if="errorStore.isApiDown || errorStore.isDatabaseDown" />
    <RouterView v-else />
  </template>
</template>
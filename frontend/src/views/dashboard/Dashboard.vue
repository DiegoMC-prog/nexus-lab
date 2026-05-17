<script setup lang="ts">
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api'; // 👈 Importamos tu instancia de Axios con interceptores
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

// 👈 Forzamos a Vue a hablar con Laravel al cargar la pantalla
onMounted(async () => {
    try {
        await api.get('/user');
    } catch (error) {
    }
});

const handleLogout = async () => {
    // 1. Limpieza local inmediata del estado

    await authStore.logout().catch(error => {
        console.error("Error al cerrar sesión en el servidor (en segundo plano):", error);
    });

    authStore.clearLocalSession();

    // 2. Redirección INSTANTÁNEA (El usuario ya ve la pantalla de login en milisegundos)
    router.push('/login');

    // 3. Disparamos la petición a Laravel de fondo. 
    // Como no lleva 'await', se procesa en paralelo sin bloquear la vista.
    
}
</script>

<template>
    <div class="p-8 bg-gray-100 min-h-screen">
        <div class="bg-white p-6 rounded-xl shadow-md max-w-sm">
            <h1 class="text-xl font-bold text-gray-800 mb-2">Bienvenido al Dashboard</h1>
            <p class="text-sm text-gray-600 mb-4">Usuario: {{ authStore.user?.name }}</p>

            <button @click="handleLogout"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                Cerrar Sesión
            </button>
        </div>
    </div>
</template>
<script setup lang="ts">
import { useErrorStore } from '@/stores/errorStore';
import { ServerCrash, Database, RefreshCw } from '@lucide/vue';
import api from '@/services/api'; // 👈 IMPORTANTE: Importa tu instancia de Axios

const errorStore = useErrorStore();

const handleRetry = async () => {
    // 1. Activamos el spinner global
    errorStore.isChecking = true;
    
    // 2. Limpiamos banderas previas de error
    errorStore.clearErrors();

    // 3. Esperamos un segundo pequeño (opcional, solo para que el usuario note que se intentó)
    // await new Promise(resolve => setTimeout(resolve, 800));

    try {
        // 4. Volvemos a consultar a Laravel
        await api.get('/user');
        
        // ¡Si llega aquí, significa que el servidor revivió (Respondió 200 OK)!
        console.log("Servidor reconectado con éxito.");
    } catch (error) {
        // Si el servidor sigue caído, la petición fallará.
        // Tu interceptor global de Axios se activará automáticamente 
        // y volverá a poner "isApiDown = true" en el store.
        // console.log("El servidor sigue sin responder.");
    } finally {
        // 5. Apagamos el spinner global
        errorStore.isChecking = false;
    }
};
</script>

<template>
    <div class="fixed inset-0 bg-linear-to-br from-gray-900 via-blue-950 to-gray-900 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full text-center">
            
            <div class="inline-flex items-center justify-center p-4 rounded-full mb-6"
                 :class="errorStore.isApiDown ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600'">
                <ServerCrash v-if="errorStore.isApiDown" class="h-12 w-12" />
                <Database v-else class="h-12 w-12" />
            </div>

            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                {{ errorStore.isApiDown ? 'Servidor No Disponible' : 'Error de Conexión Interna' }}
            </h2>
            
            <p class="text-gray-600 mb-6 text-sm">
                {{ errorStore.isApiDown 
                    ? 'No logramos comunicarnos con el sistema central. Es posible que el servidor esté en mantenimiento o apagado.' 
                    : 'Estamos experimentando problemas para leer los datos. Base de datos desconectada).' 
                }}
            </p>

            <button @click="handleRetry" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-medium transition-colors inline-flex items-center justify-center gap-2">
                <RefreshCw class="h-4 w-4" />
                Reintentar Conexión
            </button>
        </div>
    </div>
</template>
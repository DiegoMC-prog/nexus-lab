<!-- src/views/Login.vue -->
<script setup lang="ts">
import { ref } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { MonitorSmartphone, Mail, Lock, AlertCircle } from '@lucide/vue';
// Asumiendo que tienes tu AuthStore configurado con Pinia en esta ruta
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

// Estados Reactivos (Reemplazan a los useState de React)
const email = ref('');
const password = ref('');
const rememberMe = ref(false);
const error = ref('');
const loading = ref(false);

const getFingerPrint = () => {
    let fp = localStorage.getItem('device_fp');

    if (!fp) {
        // Fallback for non-secure contexts (HTTP) where crypto.randomUUID is undefined
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            fp = crypto.randomUUID();
        } else {
            fp = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }
        localStorage.setItem('device_fp', fp);
    }

    return fp;
}

const handleSubmit = async () => {
    error.value = '';
    loading.value = true;

    try {
        // Ajustado para llamar a la acción de tu Pinia authStore
        const success = await authStore.login({
            email: email.value,
            password: password.value,
            fingerprint: getFingerPrint(),
            remember: rememberMe.value 
        });

        if (success) {
            router.push('/');
        } else if (authStore.requiresOtp) {
            router.push('verify-otp');
        } else {
            error.value = 'Credenciales incorrectas. Intente nuevamente.';
        }
    } catch (err: any) {
        if (err.response && err.response.data && err.response.data.message) {
            error.value = err.response.data.message;
        } else {
            error.value = 'Error al iniciar sesión. Intente más tarde.';
        }
        //console.error("Error al logearse", err);
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div
        class="min-h-screen bg-linear-to-br from-gray-900 via-blue-900 to-gray-900 flex items-center justify-center p-4">
        <div class="w-full max-w-md">

            <!-- Logo y Título -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center bg-blue-600 p-4 rounded-2xl mb-4">
                    <MonitorSmartphone class="h-12 w-12 text-white" />
                </div>
                <h1 class="text-3xl font-semibold text-white mb-2">Control de Equipos</h1>
                <p class="text-gray-300">Laboratorio de Cómputo</p>
            </div>

            <!-- Formulario de Login -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Iniciar Sesión</h2>

                <!-- Alerta de Error -->
                <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 flex items-start gap-3">
                    <AlertCircle class="h-5 w-5 text-red-600 shrink-0 mt-0.5" />
                    <p class="text-sm text-red-800">{{ error }}</p>
                </div>

                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <!-- Campo: Email -->
                    <div>
                        <label htmlFor="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Correo Electrónico
                        </label>
                        <div class="relative">
                            <Mail class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <input v-model="email" type="email" id="email"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                                placeholder="ejemplo@lab.com" required />
                        </div>
                    </div>

                    <!-- Campo: Contraseña -->
                    <div>
                        <label htmlFor="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Contraseña
                        </label>
                        <div class="relative">
                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <input v-model="password" type="password" id="password"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                                placeholder="••••••••" required />
                        </div>
                    </div>

                    <!-- Opciones Extras -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input v-model="rememberMe" type="checkbox"
                                class="w-4 h-4 border-gray-300 rounded text-blue-600 focus:ring-blue-500" />
                            <span class="ml-2 text-sm text-gray-600">Recordarme</span>
                        </label>
                        <RouterLink to="/forgot-password" class="text-sm text-blue-600 hover:text-blue-700">
                            ¿Olvidó su contraseña?
                        </RouterLink>
                    </div>

                    <!-- Botón de Envío -->
                    <button type="submit" :disabled="loading"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ loading ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

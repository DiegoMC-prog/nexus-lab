<script setup lang="ts">
import { ref } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { MonitorSmartphone, Mail, ArrowLeft, AlertCircle, CheckCircle } from '@lucide/vue';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

// Estados Reactivos
const email = ref('');
const error = ref('');
const success = ref(false);
const loading = ref(false);

const handleSubmit = async () => {
    error.value = '';
    loading.value = true;

    try {
        // 1. Guardamos el correo en el store por si acaso
        authStore.loginEmail = email.value;

        // 2. Ejecutamos la acción que le pega al endpoint 'sendResetLink' de Laravel
        const result = await authStore.resetPassword(email.value);

        if (result) {
            success.value = true;

            // 3. CAMBIO CLAVE: Ya no redirigimos a /verify-otp.
            // Como el usuario debe ir a abrir su correo, lo ideal es regresarlo al /login 
            // después de unos segundos para que la app quede lista.
            setTimeout(() => {
                router.push({ name: 'login' });
            }, 4000); // 4 segundos para que alcance a leer el recuadro verde
        } else {
            error.value = 'No se encontró una cuenta con este correo electrónico.';
        }
    } catch (err) {
        error.value = 'Error al enviar el enlace. Intente más tarde.';
        console.error("Error en forgot-password:", err);
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div
        class="min-h-screen bg-linear-to-br from-gray-900 via-blue-900 to-gray-900 flex items-center justify-center p-4">
        <div class="w-full max-w-md">

            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center bg-blue-600 p-4 rounded-2xl mb-4">
                    <MonitorSmartphone class="h-12 w-12 text-white" />
                </div>
                <h1 class="text-3xl font-semibold text-white mb-2">NexusLab</h1>
                <p class="text-gray-300">Gestión de Laboratorios</p>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-8">
                <RouterLink to="/login"
                    class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6 text-sm">
                    <ArrowLeft class="h-4 w-4" />
                    Volver al inicio de sesión
                </RouterLink>

                <h2 class="text-2xl font-semibold text-gray-900 mb-2">¿Olvidó su contraseña?</h2>
                <p class="text-gray-600 mb-6">
                    Ingrese su correo electrónico y le enviaremos un enlace seguro para restablecer su
                    contraseña.
                </p>

                <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 flex items-start gap-3">
                    <AlertCircle class="h-5 w-5 text-red-600 shrink-0 mt-0.5" />
                    <p class="text-sm text-red-800">{{ error }}</p>
                </div>

                <div v-if="success"
                    class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 flex items-start gap-3">
                    <CheckCircle class="h-5 w-5 text-green-600 shrink-0 mt-0.5" />
                    <div>
                        <p class="text-sm font-medium text-green-800">¡Enlace enviado!</p>
                        <p class="text-sm text-green-700 mt-1">
                            Revise su bandeja de entrada. Le hemos enviado las instrucciones para recuperar su cuenta.
                        </p>
                    </div>
                </div>

                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Correo Electrónico
                        </label>
                        <div class="relative">
                            <Mail class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <input v-model="email" type="email" id="email"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                                placeholder="ejemplo@lab.com" required :disabled="success" />
                        </div>
                    </div>

                    <button type="submit" :disabled="loading || success"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ loading ? 'Enviando enlace...' : success ? 'Enlace enviado' : 'Enviar Enlace de Recuperación'
                        }}
                    </button>
                </form>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <p class="text-xs text-gray-500 text-center">
                        El enlace de recuperación será válido por 60 minutos.
                        Si no recibe el correo, revise su carpeta de spam o correo no deseado.
                    </p>
                </div>
            </div>

        </div>
    </div>
</template>
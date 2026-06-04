<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { MonitorSmartphone, Lock, AlertCircle, CheckCircle } from '@lucide/vue';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

// Parámetros de la URL
const email = ref('');
const token = ref('');

// Estados de control e interfaz
const password = ref('');
const confirmPassword = ref('');
const error = ref('');
const success = ref('');
const loading = ref(false);

// NUEVOS ESTADOS: Para la validación previa
const isValidating = ref(true); // Empieza cargando
const tokenIsValid = ref(false); // Por defecto asumimos que no es válido

onMounted(async () => {
    email.value = (route.query.email as string) || '';
    token.value = (route.query.token as string) || '';

    if (!token.value || !email.value) {
        error.value = 'Faltan parámetros de recuperación válidos.';
        isValidating.value = false;
        return;
    }

    // 👈 AQUÍ HACEMOS LA MAGIA: Preguntamos a Laravel antes de mostrar nada
    const isValid = await authStore.checkResetToken(token.value, email.value);

    if (isValid) {
        tokenIsValid.value = true;
    } else {
        error.value = 'Este enlace de recuperación ya fue utilizado o ha expirado. Por favor, solicite uno nuevo.';
    }

    isValidating.value = false; // Terminó de validar
});

const strength = computed(() => {
    const pass = password.value;
    if (pass.length === 0) return { strength: 0, label: '', color: '' };
    if (pass.length < 6) return { strength: 33, label: 'Débil', color: 'bg-red-500' };
    if (pass.length < 10) return { strength: 66, label: 'Media', color: 'bg-amber-500' };
    return { strength: 100, label: 'Fuerte', color: 'bg-green-500' };
});

const handleSubmit = async () => {
    error.value = '';
    if (password.value.length < 6) {
        error.value = 'La contraseña debe tener al menos 6 caracteres.';
        return;
    }
    if (password.value !== confirmPassword.value) {
        error.value = 'Las contraseñas no coinciden.';
        return;
    }

    loading.value = true;

    try {
        // Ejecutamos el cambio final
        const responseSuccess = await authStore.updatePassword({
            email: email.value,
            token: token.value,
            password: password.value,
            password_confirmation: confirmPassword.value
        });

        if (responseSuccess) {
            success.value = '¡Contraseña actualizada con éxito! Redirigiendo...';
            tokenIsValid.value = false; // Ocultamos el formulario inmediatamente tras el éxito

            setTimeout(() => {
                router.push({ name: 'login' });
            }, 2500);
        }
    } catch (err: any) {
        if (err.response && err.response.data && err.response.data.message) {
            error.value = err.response.data.message;
        } else {
            error.value = 'No se pudo actualizar la contraseña. Intente de nuevo.';
        }
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

                <div v-if="isValidating" class="text-center py-6">
                    <div
                        class="animate-spin inline-block w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full mb-2">
                    </div>
                    <p class="text-gray-600 text-sm">Validando enlace de seguridad...</p>
                </div>

                <div v-else>
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center justify-center bg-green-100 p-3 rounded-full mb-4">
                            <CheckCircle class="h-8 w-8 text-green-600" />
                        </div>
                        <h2 class="text-2xl font-semibold text-gray-900 mb-2">Restablecer Contraseña</h2>
                        <p class="text-gray-600" v-if="tokenIsValid">
                            Cree una nueva contraseña para <span class="font-medium text-gray-900">{{ email }}</span>
                        </p>
                    </div>

                    <div v-if="error"
                        class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 flex items-start gap-3">
                        <AlertCircle class="h-5 w-5 text-red-600 shrink-0 mt-0.5" />
                        <p class="text-sm text-red-800">{{ error }}</p>
                    </div>

                    <div v-if="success"
                        class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 flex items-start gap-3">
                        <CheckCircle class="h-5 w-5 text-green-600 shrink-0 mt-0.5" />
                        <p class="text-sm text-green-800">{{ success }}</p>
                    </div>

                    <form v-if="tokenIsValid" @submit.prevent="handleSubmit" class="space-y-4">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Nueva
                                Contraseña</label>
                            <div class="relative">
                                <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                <input v-model="password" type="password" id="password"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500-colors outline-none"
                                    placeholder="••••••••" required :disabled="loading" />
                            </div>

                            <div v-if="password" class="mt-2">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs text-gray-600">Seguridad de contraseña:</span>
                                    <span
                                        :class="['text-xs font-medium', strength.strength === 100 ? 'text-green-600' : strength.strength === 66 ? 'text-amber-600' : 'text-red-600']">{{
                                        strength.label }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div :class="['h-2 rounded-full transition-all', strength.color]"
                                        :style="{ width: strength.strength + '%' }"></div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">Confirmar
                                Nueva Contraseña</label>
                            <div class="relative">
                                <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                <input v-model="confirmPassword" type="password" id="confirmPassword"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500-colors outline-none"
                                    placeholder="••••••••" required :disabled="loading" />
                                <CheckCircle v-if="confirmPassword && password === confirmPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 h-5 w-5 text-green-500" />
                            </div>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-xs text-blue-800"><strong>Requisitos de contraseña:</strong></p>
                            <ul class="text-xs text-blue-700 mt-2 space-y-1 list-disc list-inside">
                                <li>Mínimo 6 caracteres</li>
                                <li>Se recomienda usar letras, números y símbolos</li>
                            </ul>
                        </div>

                        <button type="submit" :disabled="loading"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-medium transition-colors disabled:opacity-50">
                            {{ loading ? 'Actualizando contraseña...' : 'Restablecer Contraseña' }}
                        </button>
                    </form>

                    <div v-if="!tokenIsValid" class="mt-4 text-center">
                        <RouterLink to="/login" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                            Volver al inicio de sesión para solicitar un nuevo enlace
                        </RouterLink>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { MonitorSmartphone, ArrowLeft, AlertCircle } from '@lucide/vue';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

// 1. Recuperamos el email enviado desde el estado de la ruta de Login.vue
const email = ref(authStore.loginEmail);

// Estados Reactivos
const otp = ref(['', '', '', '', '', '']);
const error = ref('');
const loading = ref(false);
const resendCooldown = ref(0);

// Referencias para los inputs de los casilleros
const inputRefs = ref<HTMLInputElement[]>([]);

// Reemplaza el useEffect de montado en React
onMounted(() => {
    inputRefs.value[0]?.focus();
});

// Reemplaza el useEffect del contador de cooldown en React
watch(resendCooldown, (newValue) => {
    if (newValue > 0) {
        setTimeout(() => {
            resendCooldown.value--;
        }, 1000);
    }
}, { immediate: true });

const getFingerPrint = () => {
    let fp = localStorage.getItem('device_fp');
    if (!fp) {
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
};

const handleInput = (index: number, event: Event) => {
    const input = event.target as HTMLInputElement;
    const value = input.value;

    if (!/^\d*$/.test(value)) {
        otp.value[index] = ''; // Forzar limpieza si no es numérico
        return;
    }

    // Solo tomamos el último dígito ingresado
    const digit = value.slice(-1);
    otp.value[index] = digit;

    // Auto-focus al siguiente casillero
    if (digit && index < 5) {
        inputRefs.value[index + 1]?.focus();
    }

    // Auto-submit si se llena el último casillero
    if (index === 5 && digit) {
        const code = [...otp.value.slice(0, 5), digit].join('');
        handleVerify(code);
    }
};

const handleKeyDown = (index: number, e: KeyboardEvent) => {
    // Si borra estando vacío, regresa el foco al casillero anterior
    if (e.key === 'Backspace' && !otp.value[index] && index > 0) {
        inputRefs.value[index - 1]?.focus();
    }
};

const handlePaste = (e: ClipboardEvent) => {
    e.preventDefault();
    if (!e.clipboardData) return;

    const pastedData = e.clipboardData.getData('text').slice(0, 6).split('');

    pastedData.forEach((digit, index) => {
        if (/^\d$/.test(digit) && index < 6) {
            otp.value[index] = digit;
        }
    });

    // Enfocar el siguiente casillero vacío o el último disponible
    const nextEmptyIndex = otp.value.findIndex(digit => !digit);
    const focusIndex = nextEmptyIndex === -1 ? 5 : nextEmptyIndex;
    inputRefs.value[focusIndex]?.focus();

    // Auto-submit si todos se llenaron con el copiado/pegado
    if (otp.value.every(digit => digit)) {
        handleVerify(otp.value.join(''));
    }
};

const handleVerify = async (code: string) => {
    error.value = '';
    loading.value = true;

    try {
        // Consumimos tu acción de Pinia mapeando la interfaz VerifyOtpPayload
        const success = await authStore.verifyOtp({
            email: email.value,
            otp_code: code,
            fingerprint: getFingerPrint()
        });

        if (success) {
            // El dispositivo se guardó con éxito en tu backend y la sesión se inició
            router.push('/');
        } else {
            error.value = 'Código de verificación incorrecto. Intente nuevamente.';
            otp.value = ['', '', '', '', '', ''];
            inputRefs.value[0]?.focus();
        }
    } catch (err) {
        error.value = 'Error al verificar el código. Intente más tarde.';
    } finally {
        loading.value = false;
    }
};

const handleSubmit = () => {
    const code = otp.value.join('');

    if (code.length !== 6) {
        error.value = 'Por favor ingrese el código completo de 6 dígitos.';
        return;
    }

    handleVerify(code);
};

const handleResend = () => {
    resendCooldown.value = 60;
    error.value = '';
    otp.value = ['', '', '', '', '', ''];
    inputRefs.value[0]?.focus();
    // Aquí puedes invocar a un método de tu servicio si tu backend soporta reenvío de OTP
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
                <h1 class="text-3xl font-semibold text-white mb-2">NexusLab</h1>
                <p class="text-gray-300">Gestión de Laboratorios</p>
            </div>

            <!-- Formulario de Verificación OTP -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <RouterLink to="/login"
                    class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6 text-sm">
                    <ArrowLeft class="h-4 w-4" />
                    Volver
                </RouterLink>

                <h2 class="text-2xl font-semibold text-gray-900 mb-2">Verificar Código</h2>
                <p class="text-gray-600 mb-6">
                    Ingrese el código de 6 dígitos generado por su 
                    <span class="font-medium text-gray-900">Aplicación Autenticadora</span> (Google Authenticator, Authy, etc).
                </p>

                <!-- Alerta de Error -->
                <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 flex items-start gap-3">
                    <AlertCircle class="h-5 w-5 text-red-600 shrink-0 mt-0.5" />
                    <p class="text-sm text-red-800">{{ error }}</p>
                </div>

                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Contenedor de Inputs del OTP -->
                    <div class="flex gap-3 justify-center">
                        <input v-for="(digit, index) in otp" :key="index"
                            :ref="(el) => { if (el) inputRefs[index] = el as HTMLInputElement }" type="text"
                            inputmode="numeric" maxlength="1" :value="otp[index]" @input="handleInput(index, $event)"
                            @keydown="handleKeyDown(index, $event)" @paste="handlePaste"
                            class="w-12 h-14 text-center text-xl font-semibold border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                            :disabled="loading" />
                    </div>

                    <button type="submit" :disabled="loading || otp.some(digit => !digit)"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ loading ? 'Verificando...' : 'Verificar Código' }}
                    </button>
                </form>




            </div>

        </div>
    </div>
</template>
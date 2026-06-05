<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { 
    User, Mail, Phone, Building2, Key, Loader2, 
    Save, CheckCircle2, ShieldCheck, AlertCircle 
} from '@lucide/vue';
import { perfilService, type Perfil, type PerfilPayload } from '@/services/perfilService';
import { getLaravelValidationErrors } from '@/utils/errorHandler';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';

const toast = useToast();
const authStore = useAuthStore();

// --- ESTADOS REACTIVOS ---
const isLoading = ref(true);
const isSaving = ref(false);
const showSuccessAlert = ref(false);
const validationErrors = ref<Record<string, string[]> | undefined>(undefined);

// Tabs: 'personal' | 'security'
const activeTab = ref<'personal' | 'security'>('personal');

// Formularios
const form = ref<PerfilPayload>({
    name: '',
    email: '',
    telefono: '',
    password: '',
    password_confirmation: ''
});

// --- METODOS ---
const loadProfile = async () => {
    isLoading.value = true;
    try {
        const perfil = await perfilService.getPerfil();
        if (perfil) {
            form.value.name = perfil.usuario?.name || '';
            form.value.email = perfil.usuario?.email || '';
            form.value.telefono = perfil.telefono || '';
        }
    } catch (error) {
        console.error('Error al cargar datos del perfil:', error);
    } finally {
        isLoading.value = false;
    }
};

const handleSaveProfile = async () => {
    isSaving.value = true;
    validationErrors.value = undefined;
    showSuccessAlert.value = false;
    try {
        // Enviar al servicio
        await perfilService.updatePerfil({
            name: form.value.name,
            email: form.value.email,
            telefono: form.value.telefono,
            password: form.value.password || undefined,
            password_confirmation: form.value.password_confirmation || undefined
        });

        // Limpiar campos de contraseña tras guardado
        form.value.password = '';
        form.value.password_confirmation = '';

        // Actualizar Auth Store si los datos del usuario cambiaron
        if (authStore.user) {
            authStore.user.name = form.value.name;
            authStore.user.email = form.value.email;
        }

        showSuccessAlert.value = true;
        toast.success('Perfil actualizado', 'Los cambios de tu perfil y credenciales han sido guardados.');
        
        // Auto-cerrar alerta tras 4 segundos
        setTimeout(() => {
            showSuccessAlert.value = false;
        }, 4000);
    } catch (error: any) {
        console.error('Error al guardar datos del perfil:', error);
        if (error.response && error.response.status === 422) {
            validationErrors.value = getLaravelValidationErrors(error);
            toast.warning('Errores de validación', 'Por favor revisa los campos de tu perfil.');
        } else {
            toast.error('Error', 'No se pudo guardar la información de tu perfil.');
        }
    } finally {
        isSaving.value = false;
    }
};

// Iniciales del Usuario
const userInitials = computed(() => {
    const name = form.value.name || authStore.user?.name || '';
    if (!name) return 'U';
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2 && parts[0]?.[0] && parts[1]?.[0]) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
});

onMounted(() => {
    loadProfile();
});
</script>

<template>
    <div class="p-6 space-y-6 max-w-4xl mx-auto relative">
        <!-- Loader inicial -->
        <div v-if="isLoading"
            class="absolute inset-0 bg-white/40 backdrop-blur-xs z-50 flex items-center justify-center min-h-100 rounded-xl transition-all">
            <div class="bg-white p-4 rounded-xl shadow-lg border border-gray-100 flex items-center gap-3">
                <Loader2 class="w-5 h-5 text-blue-600 animate-spin" />
                <span class="text-sm font-medium text-gray-700">Cargando perfil personal...</span>
            </div>
        </div>

        <!-- Encabezado de Perfil Premium -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-md p-6 overflow-hidden relative">
            <!-- Fondo sutil de diseño de red -->
            <div class="absolute right-0 top-0 opacity-5 pointer-events-none translate-x-12 -translate-y-12 select-none">
                <Building2 class="w-96 h-96 text-slate-800" />
            </div>

            <div class="flex flex-col md:flex-row items-center gap-6 relative z-10">
                <!-- Avatar Circular Premium con Iniciales -->
                <div class="h-24 w-24 rounded-full bg-gradient-to-tr from-blue-600 via-indigo-600 to-indigo-700 text-white font-bold text-3xl flex items-center justify-center shadow-lg shadow-blue-500/20 border-4 border-white select-none transition-transform hover:scale-105 duration-300">
                    {{ userInitials }}
                </div>

                <div class="text-center md:text-left space-y-1.5 flex-1">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 justify-center md:justify-start">
                        <h1 class="text-2xl font-bold text-gray-900 leading-tight">
                            {{ form.name || 'Mi Perfil' }}
                        </h1>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-0.5 rounded-full border bg-purple-50 text-purple-700 border-purple-200 uppercase tracking-wider self-center sm:self-auto w-fit mx-auto sm:mx-0 shadow-3xs">
                            {{ authStore.user?.role || 'Docente' }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 font-medium">
                        {{ form.email || 'correo@nexus-lab.org' }}
                    </p>
                    <p class="text-xs text-gray-400 font-mono">
                        NexusLab Control - Cuenta Protegida
                    </p>
                </div>
            </div>
        </div>

        <!-- Alerta de éxito -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div v-if="showSuccessAlert"
                class="bg-emerald-50 border border-emerald-250 text-emerald-800 p-4 rounded-xl flex items-center gap-3 shadow-xs">
                <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                <div>
                    <p class="text-sm font-bold">¡Perfil Actualizado!</p>
                    <p class="text-xs text-emerald-700 font-medium">Los cambios de tu perfil y credenciales han sido guardados exitosamente.</p>
                </div>
            </div>
        </Transition>

        <!-- Navegación de Tabs -->
        <div class="border-b border-gray-200">
            <div class="flex gap-6 -mb-px">
                <button 
                    @click="activeTab = 'personal'"
                    class="py-3.5 px-1 font-semibold text-sm border-b-2 transition-all cursor-pointer flex items-center gap-2 select-none"
                    :class="activeTab === 'personal' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                >
                    <User class="w-4 h-4" />
                    Datos Personales
                </button>
                
                <button 
                    @click="activeTab = 'security'"
                    class="py-3.5 px-1 font-semibold text-sm border-b-2 transition-all cursor-pointer flex items-center gap-2 select-none"
                    :class="activeTab === 'security' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                >
                    <Key class="w-4 h-4" />
                    Seguridad y Acceso
                </button>
            </div>
        </div>

        <!-- Contenedor del Formulario -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-md p-6 md:p-8 space-y-6">
            <form @submit.prevent="handleSaveProfile" class="space-y-6">
                <!-- PANEL: DATOS PERSONALES -->
                <div v-if="activeTab === 'personal'" class="space-y-6 animate-in fade-in duration-200">
                    <div class="border-b border-gray-100 pb-3">
                        <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <User class="w-5 h-5 text-blue-600" />
                            Información de Contacto e Identidad
                        </h2>
                        <p class="text-xs text-gray-400 mt-1">Modifica tus datos de contacto registrados para recibir alertas y notificaciones.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Nombre Completo -->
                        <div class="space-y-1.5">
                            <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider block">Nombre Completo</label>
                            <div class="relative">
                                <User class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input v-model="form.name" type="text" placeholder="Ej. Diego Meléndez"
                                    :disabled="isSaving"
                                    class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all"
                                    :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.name }" />
                            </div>
                            <p v-if="validationErrors?.name" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.name[0] }}
                            </p>
                        </div>

                        <!-- Correo Institucional -->
                        <div class="space-y-1.5">
                            <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider block">Correo Electrónico</label>
                            <div class="relative">
                                <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input v-model="form.email" type="email" placeholder="Ej. diego@nexus-lab.org"
                                    :disabled="isSaving"
                                    class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all"
                                    :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.email }" />
                            </div>
                            <p v-if="validationErrors?.email" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.email[0] }}
                            </p>
                        </div>

                        <!-- Telefono -->
                        <div class="space-y-1.5">
                            <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider block">Teléfono / Celular</label>
                            <div class="relative">
                                <Phone class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input v-model="form.telefono" type="text" placeholder="Ej. +591 78945612"
                                    :disabled="isSaving"
                                    class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all"
                                    :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.telefono }" />
                            </div>
                            <p v-if="validationErrors?.telefono" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.telefono[0] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- PANEL: SEGURIDAD (CONTRASENAS) -->
                <div v-if="activeTab === 'security'" class="space-y-6 animate-in fade-in duration-200">
                    <div class="border-b border-gray-100 pb-3">
                        <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <Key class="w-5 h-5 text-blue-600" />
                            Actualización de Credenciales de Acceso
                        </h2>
                        <p class="text-xs text-gray-400 mt-1">Completa los campos solo si deseas cambiar tu contraseña actual de acceso.</p>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-xl flex items-start gap-3">
                        <ShieldCheck class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider">Políticas de Seguridad Activas</p>
                            <ul class="text-2xs text-blue-700 list-disc list-inside mt-1.5 space-y-1 font-medium">
                                <li>La nueva contraseña debe tener al menos 8 caracteres alfanuméricos.</li>
                                <li>Se requiere confirmación idéntica para procesar el cambio.</li>
                                <li>Por razones de auditoría, las sesiones en otros dispositivos podrían requerir verificación OTP.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Nueva Contraseña -->
                        <div class="space-y-1.5">
                            <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider block">Nueva Contraseña</label>
                            <div class="relative">
                                <Key class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input v-model="form.password" type="password" placeholder="Mínimo 8 caracteres"
                                    :disabled="isSaving"
                                    class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all"
                                    :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.password }" />
                            </div>
                            <p v-if="validationErrors?.password" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.password[0] }}
                            </p>
                        </div>

                        <!-- Confirmación de Contraseña -->
                        <div class="space-y-1.5">
                            <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider block">Confirmar Contraseña</label>
                            <div class="relative">
                                <Key class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input v-model="form.password_confirmation" type="password" placeholder="Repite la contraseña"
                                    :disabled="isSaving"
                                    class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones del Formulario -->
                <div class="pt-4 border-t border-gray-100 flex justify-end">
                    <button type="submit" :disabled="isSaving"
                        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-all text-xs font-semibold shadow-md shadow-blue-600/10 cursor-pointer flex items-center gap-2 group select-none">
                        <Loader2 v-slot="isSaving" v-if="isSaving" class="w-4 h-4 animate-spin" />
                        <Save v-else class="w-4 h-4 group-hover:scale-110 transition-transform" />
                        {{ isSaving ? 'Guardando Cambios...' : 'Guardar Cambios' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
/* Transiciones estéticas para un diseño responsivo y vivo */
.animate-in {
    animation-duration: 250ms;
}
</style>

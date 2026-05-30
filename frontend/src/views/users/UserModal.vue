<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { X, UserPlus, UserCheck, Loader2 } from '@lucide/vue';
import type { User, UserStatus, Role } from '@/types/user'; // CORREGIDO: Importamos Role desde tu archivo centralizado de tipos
import { userService } from '@/services/userService'; // CORREGIDO: Ruta apuntando a la carpeta central de servicios

const props = defineProps<{
    show: boolean;
    user: User | null;
    loading: boolean;
    validationErrors?: Record<string, string[]>;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'save', formData: Omit<User, 'id' | 'role'> & { role: number }): void;
}>();

// Lista reactiva para almacenar los roles cargados de la base de datos
const listaRoles = ref<Role[]>([]);

const formData = ref({
    name: '',
    email: '',
    role: null as number | null, // Maneja el ID numérico del rol seleccionado
    estado: 'activo' as UserStatus
});

const resetForm = () => {
    formData.value = {
        name: '',
        email: '',
        role: listaRoles.value.find(r => r.name === 'estudiante')?.id || null,
        estado: 'activo'
    };
};

// Función para cargar los roles desde la API
const obtenerRoles = async () => {
    try {
        const data = await userService.cargarRoles();
        listaRoles.value = data;
    } catch (error) {
        console.error("Error al cargar roles:", error);
    }
};

// Cargamos los roles apenas se monte el componente para tenerlos listos
onMounted(() => {
    obtenerRoles();
});

// Escuchamos la apertura del modal
watch(() => props.show, (newShow) => {
    if (newShow) {
        if (props.user) {
            // CORREGIDO: Buscamos el ID numérico del rol basándonos en el string que viene de Laravel
            const rolEncontrado = listaRoles.value.find(r => r.name === props.user?.role);

            formData.value = {
                name: props.user.name,
                email: props.user.email,
                role: rolEncontrado ? rolEncontrado.id : null, 
                estado: props.user.estado as UserStatus
            };
        } else {
            resetForm();
        }
    }
});

const handleSave = () => {
    if (!formData.value.name.trim() || !formData.value.email.trim() || !formData.value.role) return;
    emit('save', { ...formData.value } as Omit<User, 'id' | 'role'> & { role: number });
};
</script>

<template>
    <Transition enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 backdrop-blur-none" enter-to-class="opacity-100 backdrop-blur-md"
        leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 backdrop-blur-md"
        leave-to-class="opacity-0 backdrop-blur-none">
        <div v-if="show"
            class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div
                class="bg-white rounded-xl border border-gray-100 max-w-md w-full shadow-2xl relative overflow-hidden flex flex-col">

                <!-- Encabezado -->
                <div class="px-6 pt-6 pb-4 flex items-start justify-between border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div :class="user ? 'bg-blue-50 text-blue-600' : 'bg-green-50 text-green-600'"
                            class="p-2.5 rounded-lg">
                            <component :is="user ? UserCheck : UserPlus" class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ user ? 'Editar Usuario' : 'Nuevo Usuario' }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ user ? 'Modifica los accesos y credenciales del perfil.' : 'Registra una nueva cuenta en el sistema.' }}
                            </p>
                        </div>
                    </div>
                    <!-- Deshabilitamos la 'X' si está guardando -->
                    <button @click="emit('close')" :disabled="loading"
                        class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1.5 rounded-lg transition-colors cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Formulario (Todos con :disabled="loading") -->
                <div class="p-6 space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Nombre
                            Completo</label>
                        <input v-model="formData.name" type="text" placeholder="Ej. Carlos Mendoza" :disabled="loading"
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-sm transition-all placeholder:text-gray-400 disabled:bg-gray-50 disabled:text-gray-400"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': validationErrors?.name }" />
                        <p v-if="validationErrors?.name" class="text-xs text-red-500 mt-1 block">
                            {{ validationErrors.name[0] }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Correo
                            Electrónico</label>
                        <input v-model="formData.email" type="email" placeholder="nombre@universidad.edu"
                            :disabled="loading"
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-sm transition-all placeholder:text-gray-400 disabled:bg-gray-50 disabled:text-gray-400"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': validationErrors?.email }" />
                        <p v-if="validationErrors?.email" class="text-xs text-red-500 mt-1 block">
                            {{ validationErrors.email[0] }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Rol
                                asignado</label>
                            <select v-model="formData.role" :disabled="loading"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-sm transition-all cursor-pointer capitalize disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': validationErrors?.role }">
                                <option :value="null" disabled>Seleccione un rol</option>
                                <option v-for="rol in listaRoles" :key="rol.id" :value="rol.id">
                                    {{ rol.name }}
                                </option>
                            </select>
                            <p v-if="validationErrors?.role" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.role[0] }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Estado de
                                cuenta</label>
                            <select v-model="formData.estado" :disabled="loading"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-sm transition-all cursor-pointer disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': validationErrors?.estado }">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                                <option value="bloqueado">Bloqueado</option>
                            </select>
                            <p v-if="validationErrors?.estado" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.estado[0] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2.5">
                    <!-- Botón Cancelar se bloquea mientras guarda -->
                    <button @click="emit('close')" :disabled="loading"
                        class="px-4 py-2 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-100 hover:border-gray-300 transition-all text-sm font-medium cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        Cancelar
                    </button>

                    <!-- Botón Guardar cambia dinámicamente -->
                    <button @click="handleSave"
                        :disabled="!formData.name || !formData.email || !formData.role || loading"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-all text-sm font-medium shadow-sm cursor-pointer flex items-center gap-2">
                        <!-- Icono animado de carga si loading es true -->
                        <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
                        {{ loading ? 'Guardando...' : (user ? 'Guardar Cambios' : 'Crear Usuario') }}
                    </button>
                </div>

            </div>
        </div>
    </Transition>
</template>
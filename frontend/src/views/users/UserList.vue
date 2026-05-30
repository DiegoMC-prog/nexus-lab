<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import {
    Search, Plus, Edit, Trash2, Mail, CheckCircle2,
    XCircle, Lock, ChevronLeft, ChevronRight
} from '@lucide/vue';
import type { User, UserRole, UserStatus } from '@/types/user';
import { userService } from '@/services/userService'; // Ajusta la ruta a tu servicio

// Modales modulares internos
import UserModal from './UserModal.vue';
import UserDeleteModal from './UserDeleteModal.vue';
import BasePagination from '@/components/BasePagination.vue';
import { useAuthStore } from '@/stores/auth';
import { getLaravelValidationErrors } from '@/utils/errorHandler';

// Configuración visual para Roles
const rolesConfig: Record<string, { displayName: string; color: string }> = {
    admin: { displayName: 'Administrador', color: '#ef4444' },
    docente: { displayName: 'Docente', color: '#f59e0b' },
    estudiante: { displayName: 'Estudiante', color: '#3b82f6' },
    mantenimiento: { displayName: 'Mantenimiento', color: '#10b981' }
};

// --- ESTADO REACTIVO DE LA API ---
const users = ref<User[]>([]);
const isLoading = ref(true);
const isSaving = ref(false);
const authStore = useAuthStore();

const isInitializing = ref(true);

// Errores de validación del backend
const validationErrors = ref<Record<string, string[]> | undefined>(undefined);

// Filtros
const searchTerm = ref('');
const filterRole = ref<UserRole | 'all'>('all');
const filterStatus = ref<UserStatus | 'all'>('all');

// Estado de la Paginación
const currentPage = ref(1);
const lastPage = ref(1);
const totalUsers = ref(0);
const perPage = ref(10);

// Control de Modales
const isFormDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const selectedUser = ref<User | null>(null);

// --- FUNCIÓN CENTRAL: PETICIÓN AL BACKEND ---
const fetchUsers = async () => {
    isLoading.value = true;
    try {
        const response = await userService.getUsers({
            page: currentPage.value,
            search: searchTerm.value || undefined,
            role: filterRole.value === 'all' ? undefined : filterRole.value,
            estado: filterStatus.value === 'all' ? undefined : filterStatus.value,
        });

        // Seteamos la respuesta que viene formateada por el through() de Laravel
        users.value = response.data;
        currentPage.value = response.current_page;
        lastPage.value = response.last_page;
        totalUsers.value = response.total;
        perPage.value = response.per_page;
    } catch (error) {
        console.error('Error al cargar los usuarios desde la API:', error);
    } finally {
        isLoading.value = false;
    }
};

// --- OBSERVADORES (WATCHERS) ---
// Si el usuario escribe o cambia un filtro, reiniciamos siempre a la página 1
watch([searchTerm, filterRole, filterStatus], () => {
    currentPage.value = 1;
    fetchUsers();
});

// Si cambia la página, ejecutamos la consulta
watch(currentPage, () => {
    fetchUsers();
});

// Carga inicial optimizada
onMounted(async () => {
    // Iniciamos la carga explícitamente
    isLoading.value = true;
    isInitializing.value = true;

    try {
        // Dispara ambas peticiones al mismo tiempo (Multitasking real)
        await Promise.all([
            authStore.refreshPermisos(),
            fetchUsers()
        ]);
    } catch (error) {
        // fetchUsers ya tiene su propio catch, pero por seguridad:
        console.error('Error en la carga inicial general:', error);
    } finally {
        // Nos aseguramos de apagar el loading pase lo que pase
        isLoading.value = false;
        isInitializing.value = false;
    }
});

// --- ACCIONES Y ORQUESTACIÓN ---
const openCreateDialog = () => {
    selectedUser.value = null;
    validationErrors.value = undefined;
    isFormDialogOpen.value = true;
};

const openEditDialog = (user: User) => {
    selectedUser.value = user;
    validationErrors.value = undefined;
    isFormDialogOpen.value = true;
};

const openDeleteDialog = (user: User) => {
    selectedUser.value = user;
    isDeleteDialogOpen.value = true;
};

const handleSaveUser = async (formData: any) => {
    isSaving.value = true;
    validationErrors.value = undefined;
    try {
        if (selectedUser.value?.id) {
            // Edición: Enviamos el ID y los nuevos datos (el rol viaja como ID numérico)
            await userService.actualizarUser(selectedUser.value.id, formData);
        } else {
            // Creación
            await userService.crearUser(formData);
        }
        isFormDialogOpen.value = false;
        selectedUser.value = null;
        await fetchUsers(); // Recargar la página actual
    } catch (error: any) {
        console.error('Error al guardar el usuario:', error);
        if (error.response && error.response.status === 422) {
            validationErrors.value = getLaravelValidationErrors(error);
        }
    } finally {
        isSaving.value = false
    }
};

const handleDeleteUser = async () => {
    if (!selectedUser.value?.id) return;
    try {
        await userService.eliminarUser(selectedUser.value.id);
        isDeleteDialogOpen.value = false;
        selectedUser.value = null;
        await fetchUsers(); // Recargar la tabla
    } catch (error) {
        console.error('Error al eliminar el usuario:', error);
    }
};

// Configuración de Badge de Estado
const getStatusConfig = (estado: string) => {
    const config: Record<string, { icon: any; classes: string }> = {
        activo: { icon: CheckCircle2, classes: 'bg-green-100 text-green-700 border-green-200' },
        inactivo: { icon: XCircle, classes: 'bg-gray-100 text-gray-700 border-gray-200' },
        bloqueado: { icon: Lock, classes: 'bg-red-100 text-red-700 border-red-200' }
    };
    return config[estado] || { icon: XCircle, classes: 'bg-gray-100 text-gray-700 border-gray-200' };
};
</script>

<template>
    <div class="p-8 space-y-6">
        <!-- Encabezado -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-gray-900 mb-2">Gestión de Usuarios</h1>
                <p class="text-gray-600">{{ totalUsers }} usuarios registrados en el sistema</p>
            </div>
            <!-- Si está inicializando, muestra un esqueleto animado -->
            <div v-if="isInitializing" class="w-32 h-10 bg-gray-200 animate-pulse rounded-md"></div>

            <!-- Si ya terminó, evalúa el permiso real -->
            <button v-else-if="authStore.can('usuarios.crear')" @click="openCreateDialog"
                class="bg-blue-600 hover:bg-blue-700 text-white gap-2 flex items-center px-4 py-2 rounded-md transition-colors text-sm font-medium shadow-sm cursor-pointer">
                <Plus class="w-4 h-4" />
                Nuevo Usuario
            </button>
        </div>

        <!-- Filtros de Búsqueda -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input v-model="searchTerm" type="text" placeholder="Buscar por nombre o email..."
                        class="pl-10 w-full px-3 py-2 bg-white border border-gray-200 rounded-md text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" />
                </div>

                <select v-model="filterRole"
                    class="bg-white border border-gray-200 text-gray-900 px-3 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="all">Todos los roles</option>
                    <option value="admin">Administrador</option>
                    <option value="docente">Docente</option>
                    <option value="estudiante">Estudiante</option>
                    <option value="mantenimiento">Mantenimiento</option>
                </select>

                <select v-model="filterStatus"
                    class="bg-white border border-gray-200 text-gray-900 px-3 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="all">Todos los estados</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                    <option value="bloqueado">Bloqueado</option>
                </select>
            </div>
        </div>

        <!-- Tabla de Datos -->
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm relative">
            <!-- Efecto de Carga Opcional -->
            <div v-if="isLoading"
                class="absolute inset-0 bg-white/50 flex items-center justify-center z-10 backdrop-blur-[1px]">
                <span class="text-sm font-medium text-gray-600">Cargando datos...</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th
                                class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Usuario</th>
                            <th
                                class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Contacto</th>
                            <th
                                class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Rol</th>
                            <th
                                class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Estado</th>
                            <th
                                class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-full bg-linear-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-bold text-sm">
                                        {{user.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()}}
                                    </div>
                                    <div>
                                        <p class="text-gray-900 font-medium text-sm">{{ user.name }}</p>
                                        <p class="text-gray-400 text-xs">ID: {{ user.id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-gray-700 text-sm flex items-center gap-2">
                                    <Mail class="w-3 h-3 text-gray-400" />
                                    {{ user.email }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="user.role"
                                    :style="{ backgroundColor: rolesConfig[user.role]?.color || '#6b7280' }"
                                    class="text-xs px-2 py-0.5 text-white font-medium rounded inline-block">
                                    {{ rolesConfig[user.role]?.displayName || user.role }}
                                </span>
                                <span v-else class="text-xs text-gray-400 italic">Sin rol asignado</span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="border text-xs px-2 py-0.5 rounded font-medium inline-flex items-center gap-1"
                                    :class="getStatusConfig(user.estado).classes">
                                    <component :is="getStatusConfig(user.estado).icon" class="w-3 h-3" />
                                    {{ user.estado.charAt(0).toUpperCase() + user.estado.slice(1) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1">
                                    <button v-if="authStore.can('usuarios.editar')" @click="openEditDialog(user)"
                                        class="p-1.5 rounded text-blue-600 hover:text-blue-700 hover:bg-blue-50 transition-colors cursor-pointer">
                                        <Edit class="w-4 h-4" />
                                    </button>
                                    <button v-if="authStore.can('usuarios.eliminar')" @click="openDeleteDialog(user)"
                                        class="p-1.5 rounded text-red-600 hover:text-red-700 hover:bg-red-50 transition-colors cursor-pointer">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.length === 0 && !isLoading">
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-400 italic">
                                No se encontraron usuarios que coincidan con los filtros.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- --- COMPONENTE PAGINADOR DINÁMICO --- -->
            <BasePagination v-model="currentPage" :last-page="lastPage" :total="totalUsers" />
        </div>

        <!-- Modales Auxiliares -->
        <UserModal :show="isFormDialogOpen" :user="selectedUser" :loading="isSaving" :validation-errors="validationErrors" @close="isFormDialogOpen = false"
            @save="handleSaveUser" />
        <UserDeleteModal :show="isDeleteDialogOpen" :user="selectedUser" @close="isDeleteDialogOpen = false"
            @confirm="handleDeleteUser" />
    </div>
</template>
<script setup lang="ts">
import { ref, computed, type Component, onMounted } from 'vue';
import { Shield, Users, Lock, Edit, CheckCircle2, Circle, Monitor, BarChart3, Clock, Loader2 } from '@lucide/vue';
import type { ApiRolesResponse, Permission, Role } from '@/types/role';

import RoleEditModal from './RoleEditModal.vue';
import { roleService } from '@/services/roleService';

const rawApiData = ref<ApiRolesResponse>({ roles: [] });
const allSystemPermissions = ref<string[]>([]);
const loading = ref(true);
const isSavingModal = ref(false); // <-- NUEVA VARIABLE: Maneja el estado asíncrono de guardado

onMounted(async () => {
    try {
        const [rolesData, permissionsData] = await Promise.all([
            roleService.getRoles(),
            roleService.getAllPermissions()
        ]);
        rawApiData.value = rolesData;
        allSystemPermissions.value = permissionsData;
    } catch (error) {
        console.error("Error cargando políticas RBAC desde la base de datos:", error);
    } finally {
        loading.value = false;
    }
});

// Diccionarios de traducción y configuraciones estéticas
const moduleTranslations: Record<string, string> = {
    usuarios: 'Usuarios', roles: 'Roles y Accesos', horarios: 'Horarios',
    laboratorios: 'Laboratorios', monitoreo: 'Monitoreo Técnico', data: 'Gestión de Datos'
};

const actionTranslations: Record<string, string> = {
    ver: 'Visualizar', crear: 'Crear / Registrar', editar: 'Modificar', eliminar: 'Remover / Dar de baja'
};

const categoryIcons: Record<string, Component> = {
    usuarios: Users, roles: Shield, horarios: Clock, laboratorios: Monitor, monitoreo: BarChart3
};

const roleUiConfig: Record<string, { displayName: string; description: string; color: string; userCount: number }> = {
    admin: { displayName: 'Administrador', description: 'Acceso total y sin restricciones a todos los módulos operativos y de configuración de NexusLab.', color: '#7c3aed', userCount: 3 },
    docente: { displayName: 'Docente', description: 'Supervisión de laboratorios asignados a sus clases y consulta de cronogramas.', color: '#10b981', userCount: 12 },
    estudiante: { displayName: 'Estudiante', description: 'Permisos mínimos de lectura. Consulta disponibilidad de sus computadoras asignadas.', color: '#3b82f6', userCount: 145 }
};

const selectedRole = ref<Role | null>(null);
const isEditDialogOpen = ref(false);
const activeTab = ref('usuarios');

const mockPermissions = computed<Permission[]>(() => {
    return allSystemPermissions.value.map(id => {
        const [moduleKey, actionKey] = id.split('.');
        const cleanModule = moduleTranslations[moduleKey || ''] || moduleKey;
        const cleanAction = actionTranslations[actionKey || ''] || actionKey;
        return {
            id,
            name: `${cleanAction} ${cleanModule}`,
            description: `Permite realizar la acción de ${cleanAction?.toLowerCase()} en el módulo de ${cleanModule?.toLowerCase()}.`,
            category: moduleKey || 'general'
        };
    });
});

const rolesList = computed<Role[]>(() => {
    return rawApiData.value.roles.map(role => {
        const ui = roleUiConfig[role.name] || { displayName: role.name, description: 'Rol del sistema.', color: '#6b7280', userCount: 0 };
        return {
            id: String(role.id), name: role.name, displayName: ui.displayName,
            description: ui.description, color: ui.color, userCount: ui.userCount,
            permissions: role.permissions
        };
    });
});

const permissionsByCategory = computed(() => {
    return mockPermissions.value.reduce((acc, permission) => {
        const category = permission.category;
        if (!acc[category]) { acc[category] = []; }
        acc[category]?.push(permission);
        return acc;
    }, {} as Record<string, Permission[]>);
});

const filteredPermissionsForMatrix = computed(() => {
    return mockPermissions.value.filter(p => p.category === activeTab.value);
});

const openEditDialog = (role: Role) => {
    selectedRole.value = role;
    isEditDialogOpen.value = true;
};

// SOLUCIÓN AL BUG ASÍNCRONO:
const saveRolePermissions = async (updatedPermissionsIds: string[]) => {
    if (!selectedRole.value) return;
    const roleId = selectedRole.value.id;

    isSavingModal.value = true; // Iniciamos la carga en el modal

    try {
        // Enviamos la petición real a Laravel
        await roleService.updateRolePermissions(roleId, updatedPermissionsIds);

        // SI LA API RESPONDE OK: Actualizamos el estado reactivo en el frontend
        const targetRole = rawApiData.value.roles.find(r => r.id === Number(roleId));
        if (targetRole) {
            targetRole.permissions = updatedPermissionsIds;
        }

        // Cerramos el modal ÚNICAMENTE si la base de datos se actualizó con éxito
        isEditDialogOpen.value = false;
    } catch (error) {
        console.error("Error al guardar los nuevos permisos en el servidor:", error);
        alert("Ocurrió un error en el servidor. Los cambios no se guardaron.");
        // Al lanzar el error, no cerramos el modal, manteniendo los checkboxes listos para reintentar
    } finally {
        isSavingModal.value = false; // Apagamos la carga pase lo que pase
    }
};
</script>

<template>
    <div class="p-8 space-y-6 bg-gray-50/50 min-h-screen">

        <div>
            <h1 class="text-3xl font-semibold text-gray-900 mb-2">
                Gestión de Roles y Permisos
            </h1>
            <p class="text-gray-600">
                <span v-if="loading" class="inline-flex items-center gap-2">
                    <Loader2 class="w-4 h-4 animate-spin text-blue-600" /> Sincronizando políticas RBAC...
                </span>
                <span v-else>
                    {{ rolesList.length }} roles configurados · Control de acceso basado en roles (RBAC)
                </span>
            </p>
        </div>

        <div v-if="loading" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="n in 3" :key="n"
                    class="bg-white border border-gray-100 rounded-xl p-6 space-y-4 animate-pulse shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gray-200 rounded-lg"></div>
                        <div class="space-y-2 flex-1">
                            <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="h-3 bg-gray-200 rounded w-full"></div>
                        <div class="h-3 bg-gray-200 rounded w-5/6"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-2">
                        <div v-for="x in 4" :key="x" class="h-10 bg-gray-100/80 rounded border border-gray-200/40">
                        </div>
                    </div>
                    <div class="h-9 bg-gray-200 rounded w-full mt-2"></div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm animate-pulse space-y-4">
                <div class="h-5 bg-gray-200 rounded w-1/4"></div>
                <div class="flex gap-2">
                    <div v-for="t in 4" :key="t" class="h-9 bg-gray-100 rounded w-32"></div>
                </div>
                <div class="space-y-3 pt-2">
                    <div class="h-10 bg-gray-50 rounded w-full"></div>
                    <div v-for="r in 4" :key="r" class="h-12 bg-white rounded border border-gray-100 w-full"></div>
                </div>
            </div>
        </div>

        <div v-else class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="role in rolesList" :key="role.id"
                    class="bg-white border border-gray-200 rounded-xl p-6 hover:border-gray-300 transition-all hover:shadow-md flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg flex items-center justify-center border-2"
                                    :style="{ backgroundColor: `${role.color}20`, borderColor: role.color }">
                                    <Shield class="w-6 h-6" :style="{ color: role.color }" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ role.displayName }}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <Users class="w-3 h-3 text-gray-400" />
                                        <span class="text-gray-500 text-xs">
                                            {{ role.userCount }} usuarios
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                            {{ role.description }}
                        </p>

                        <div class="space-y-3 mb-6">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Permisos otorgados:</span>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded border"
                                    :style="{ borderColor: role.color, color: role.color, backgroundColor: `${role.color}10` }">
                                    {{ role.permissions.length }} / {{ allSystemPermissions.length }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div v-for="(perms, category) in permissionsByCategory" :key="category"
                                    class="bg-gray-50 rounded p-2 border border-gray-100">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-[11px] text-gray-600 truncate flex items-center gap-1">
                                            <component :is="categoryIcons[category] || Shield"
                                                class="w-3.5 h-3.5 shrink-0" :style="{ color: role.color }" />
                                            {{ moduleTranslations[category] || category }}
                                        </span>
                                        <span class="text-[10px] font-medium text-gray-500">
                                            {{perms.filter(p => role.permissions.includes(p.id)).length}}/{{
                                                perms.length }}
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-1">
                                        <div class="h-1 rounded-full transition-all duration-300" :style="{
                                            width: `${(perms.filter(p => role.permissions.includes(p.id)).length / perms.length) * 100}%`,
                                            backgroundColor: role.color
                                        }" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button @click="openEditDialog(role)"
                        class="w-full flex items-center justify-center bg-white hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 border border-gray-200 rounded-lg shadow-sm transition-colors text-sm mt-2">
                        <Edit class="w-4 h-4 mr-2 text-gray-500" />
                        Editar Permisos
                    </button>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <Lock class="w-5 h-5 text-blue-600" />
                    Matriz de Permisos
                </h2>

                <div class="w-full">
                    <div
                        class="flex gap-1 bg-gray-100 p-1 rounded-lg border border-gray-200 w-max mb-4 overflow-x-auto max-w-full">
                        <button v-for="(_, key) in permissionsByCategory" :key="key" @click="activeTab = key" :class="[
                            'px-4 py-2 text-sm font-medium rounded-md transition-all flex items-center gap-2 select-none whitespace-nowrap',
                            activeTab === key ? 'bg-white text-gray-900 shadow-sm border border-gray-200/50' : 'text-gray-600 hover:text-gray-900'
                        ]">
                            <component :is="categoryIcons[key] || Shield" class="w-4 h-4" /> {{ moduleTranslations[key]
                                || key }}
                        </button>
                    </div>

                    <div class="overflow-x-auto border border-gray-100 rounded-lg">
                        <table class="w-full border-collapse">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-1/4">
                                        Permiso
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-2/5">
                                        Descripción
                                    </th>
                                    <th v-for="role in rolesList" :key="role.id"
                                        class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider"
                                        :style="{ color: role.color }">
                                        {{ role.displayName }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="permission in filteredPermissionsForMatrix" :key="permission.id"
                                    class="hover:bg-gray-50/80 transition-colors">
                                    <td class="px-6 py-4 text-gray-900 font-medium text-sm whitespace-nowrap">
                                        {{ permission.name }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 text-sm">
                                        {{ permission.description }}
                                    </td>

                                    <td v-for="role in rolesList" :key="role.id" class="px-4 py-4 text-center">
                                        <CheckCircle2 v-if="role.permissions.includes(permission.id)"
                                            class="w-5 h-5 mx-auto transition-transform duration-200 scale-100"
                                            :style="{ color: role.color }" />
                                        <Circle v-else class="w-5 h-5 text-gray-200 mx-auto" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-8 space-y-6 bg-gray-50/50 min-h-screen">
            <RoleEditModal v-model="isEditDialogOpen" :role="selectedRole" :permissions="mockPermissions"
                :is-saving="isSavingModal" @save="saveRolePermissions" />
        </div>
    </div>
</template>
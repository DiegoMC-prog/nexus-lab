<script setup lang="ts">
import { ref, watch, computed, type Component } from 'vue';
// Añadimos Loader2 para el feedback visual de guardado
import { Shield, X, Monitor, Users, BarChart3, Clock, Loader2 } from '@lucide/vue';
import type { Permission, Role } from '@/types/role';

// Props y Emits
const props = defineProps<{
    modelValue: boolean;
    role: Role | null;
    permissions: Permission[];
    isSaving: boolean; // <-- NUEVA PROP: Controla el estado de carga del botón
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'save', updatedPermissions: string[]): void;
}>();

const selectedPermissions = ref<string[]>([]);

const categoryNames: Record<string, string> = {
    usuarios: 'Usuarios y Seguridad',
    laboratorios: 'Laboratorios',
    horarios: 'Horarios',
    monitoreo: 'Monitoreo Técnico'
};

const categoryIcons: Record<string, Component> = {
    usuarios: Users,
    laboratorios: Monitor,
    horarios: Clock,
    monitoreo: BarChart3
};

// SOLUCIÓN AL BUG: Escuchamos tanto el rol como la apertura del modal (modelValue)
watch(
    [() => props.role, () => props.modelValue],
    ([newRole, isOpen]) => {
        // Cada vez que el modal se abra, reiniciamos los checkboxes con la data real del prop
        if (isOpen && newRole) {
            selectedPermissions.value = [...newRole.permissions];
        }
    },
    { immediate: true }
);

const permissionsByCategory = computed(() => {
    return props.permissions.reduce((acc, permission) => {
        const category = permission.category;
        if (!acc[category]) {
            acc[category] = [];
        }
        acc[category]?.push(permission);
        return acc;
    }, {} as Record<string, Permission[]>);
});

const closeModal = () => {
    if (props.isSaving) return; // Evita cerrar el modal si se está guardando
    emit('update:modelValue', false);
};

const handleSave = () => {
    // Emitimos el evento pero NO cerramos el modal aquí. 
    // Dejamos que el componente padre lo cierre si la API responde OK.
    emit('save', selectedPermissions.value);
};
</script>

<template>
    <Transition name="modal-fade">
        <div v-if="modelValue"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 backdrop-blur-md bg-black/40 transition-all duration-300"
            @click.self="closeModal">

            <div
                class="bg-white border border-gray-200 rounded-xl max-w-3xl w-full max-h-[80vh] flex flex-col shadow-2xl overflow-hidden transform transition-all duration-300 scale-100">

                <div class="p-6 border-b border-gray-100 flex items-start justify-between bg-white">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center border-2" :style="{
                            backgroundColor: `${role?.color}20`,
                            borderColor: role?.color
                        }">
                            <Shield class="w-5 h-5" :style="{ color: role?.color }" />
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">
                                Editar Permisos: {{ role?.displayName }}
                            </h2>
                            <p class="text-gray-500 text-sm mt-0.5">
                                {{ role?.description }}
                            </p>
                        </div>
                    </div>

                    <button @click="closeModal" :disabled="isSaving"
                        class="text-gray-400 hover:text-gray-600 p-1.5 hover:bg-gray-50 rounded-lg transition-colors disabled:opacity-50">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-6 bg-white">
                    <div v-for="(perms, category) in permissionsByCategory" :key="category" class="space-y-3">
                        <h3
                            class="text-base font-semibold text-gray-900 flex items-center gap-2 border-b border-gray-200 pb-2">
                            <component :is="categoryIcons[category] || Shield" class="w-4 h-4 shrink-0"
                                :style="{ color: role?.color }" />
                            {{ categoryNames[category] || category }}
                            <span
                                class="ml-auto text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200">
                                {{perms.filter(p => selectedPermissions.includes(p.id)).length}}/{{ perms.length }}
                            </span>
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <label v-for="permission in perms" :key="permission.id" :for="permission.id"
                                class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-100/50 transition-all cursor-pointer select-none">
                                <input type="checkbox" :id="permission.id" :value="permission.id"
                                    v-model="selectedPermissions" :disabled="isSaving"
                                    class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-offset-0 transition-colors disabled:opacity-50" />
                                <div class="flex-1">
                                    <span class="text-gray-900 font-medium text-sm block">
                                        {{ permission.name }}
                                    </span>
                                    <p class="text-gray-500 text-xs mt-0.5">
                                        {{ permission.description }}
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                    <button @click="closeModal" :disabled="isSaving"
                        class="px-4 py-2 border border-gray-200 text-gray-700 bg-white hover:bg-gray-50 rounded-lg font-medium text-sm transition-colors shadow-sm disabled:opacity-50">
                        Cancelar
                    </button>
                    <button @click="handleSave" :disabled="isSaving"
                        class="px-4 py-2 text-white rounded-lg font-medium text-sm transition-colors shadow-sm flex items-center gap-2 disabled:opacity-80"
                        :style="{ backgroundColor: role?.color }">
                        <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
                        {{ isSaving ? 'Guardando...' : 'Guardar Cambios' }}
                    </button>
                </div>

            </div>
        </div>
    </Transition>
</template>

<style scoped>
/* Los estilos de animación se mantienen exactamente iguales */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.25s ease;
}

.modal-fade-enter-active .scale-100,
.modal-fade-leave-active .scale-100 {
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-fade-enter-from .scale-100,
.modal-fade-leave-to .scale-100 {
    transform: scale(0.95);
}
</style>
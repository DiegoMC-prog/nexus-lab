<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { X, Users, Loader2 } from '@lucide/vue';
import type { Grupo, GrupoFormData } from '@/types/grupo';

const props = defineProps<{
    modelValue: boolean;
    grupo: Grupo | null;
    isSaving: boolean;
    materias: { id: number; nombre: string; }[];
    validationErrors?: Record<string, string[]>;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'save', data: GrupoFormData): void;
}>();

const formData = ref<GrupoFormData>({
    materia_id: null,
    nombre: '',
    gestion: '',
    cupo_maximo: null
});

const isEditing = computed(() => !!props.grupo);

// Sincroniza y limpia los campos reactivos del formulario cada vez que el modal cambia su estado de visibilidad
watch(() => props.modelValue, (isOpen) => {
    if (isOpen) {
        if (props.grupo) {
            formData.value = {
                materia_id: props.grupo.materia_id,
                nombre: props.grupo.nombre,
                gestion: props.grupo.gestion,
                cupo_maximo: typeof props.grupo.cupo_maximo === 'number' ? props.grupo.cupo_maximo : null
            };
        } else {
            formData.value = {
                materia_id: null,
                nombre: '',
                gestion: '',
                cupo_maximo: null
            };
        }
    }
});

const closeModal = () => {
    if (!props.isSaving) {
        emit('update:modelValue', false);
    }
};

const handleSubmit = () => {
    emit('save', {
        ...formData.value
    });
};
</script>

<template>
    <Transition name="modal">
        <div v-if="modelValue"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div
                class="bg-white rounded-xl shadow-xl border border-gray-100 max-w-md w-full overflow-hidden transform transition-all scale-100">

                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <Users class="w-5 h-5 text-blue-600" />
                        {{ isEditing ? 'Editar Grupo Académico' : 'Nuevo Grupo Académico' }}
                    </h2>
                    <button @click="closeModal" :disabled="isSaving"
                        class="text-gray-400 hover:text-gray-600 p-1.5 hover:bg-gray-50 rounded-lg transition-colors disabled:opacity-50">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Materia Asociada
                        </label>
                        <select v-model="formData.materia_id" required :disabled="isSaving"
                            class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all"
                            :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.materia_id }">
                            <option :value="null" disabled>Selecciona una materia...</option>
                            <option v-for="materia in materias" :key="materia.id" :value="materia.id">
                                {{ materia.nombre }}
                            </option>
                        </select>
                        <p v-if="validationErrors?.materia_id" class="text-xs text-red-500 mt-1 block">
                            {{ validationErrors.materia_id[0] }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Nombre del Grupo (Sección)
                        </label>
                        <input v-model="formData.nombre" type="text" required placeholder="Ej: Grupo 1, Grupo A"
                            :disabled="isSaving"
                            class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all"
                            :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.nombre }" />
                        <p v-if="validationErrors?.nombre" class="text-xs text-red-500 mt-1 block">
                            {{ validationErrors.nombre[0] }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Gestión / Período
                        </label>
                        <input v-model="formData.gestion" type="text" required placeholder="Ej: 1/2026, 2/2026"
                            :disabled="isSaving"
                            class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all"
                            :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.gestion }" />
                        <p v-if="validationErrors?.gestion" class="text-xs text-red-500 mt-1 block">
                            {{ validationErrors.gestion[0] }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Cupo Máximo de Alumnos
                        </label>
                        <input v-model="formData.cupo_maximo" type="number" placeholder="Ej: 30 (Opcional)" :disabled="isSaving" min="1"
                            class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all"
                            :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.cupo_maximo }" />
                        <p v-if="validationErrors?.cupo_maximo" class="text-xs text-red-500 mt-1 block">
                            {{ validationErrors.cupo_maximo[0] }}
                        </p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 -mx-6 -mb-6 p-4">
                        <button type="button" @click="closeModal" :disabled="isSaving"
                            class="px-4 py-2 border border-gray-200 text-gray-700 hover:bg-gray-50 font-medium rounded-lg text-sm transition-colors disabled:opacity-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSaving"
                            class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm gap-2 transition-all text-sm disabled:opacity-70 min-w-30">
                            <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
                            <span>{{ isSaving ? 'Guardando...' : (isEditing ? 'Guardar Cambios' : 'Crear Grupo')
                            }}</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </Transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.25s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>

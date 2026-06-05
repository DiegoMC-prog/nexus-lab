<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { X, Calendar, Loader2 } from '@lucide/vue';
import type { Semestre, SemestreFormData } from '@/types/semestre';

const props = defineProps<{
    modelValue: boolean;
    semestre: Semestre | null;
    isSaving: boolean;
    validationErrors?: Record<string, string[]>;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'save', data: SemestreFormData): void;
}>();

const formData = ref<SemestreFormData>({
    nombre: '',
    fecha_inicio: '',
    fecha_fin: ''
});

const isEditing = computed(() => !!props.semestre);

// Sincroniza y limpia los campos reactivos del formulario cada vez que el modal cambia su estado de visibilidad
watch(() => props.modelValue, (isOpen) => {
    if (isOpen) {
        if (props.semestre) {
            formData.value = {
                nombre: props.semestre.nombre,
                fecha_inicio: props.semestre.fecha_inicio,
                fecha_fin: props.semestre.fecha_fin
            };
        } else {
            formData.value = { nombre: '', fecha_inicio: '', fecha_fin: '' };
        }
    }
});

const closeModal = () => {
    if (!props.isSaving) {
        emit('update:modelValue', false);
    }
};

const handleSubmit = () => {
    emit('save', { ...formData.value });
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
                        <Calendar class="w-5 h-5 text-blue-600" />
                        {{ isEditing ? 'Editar Semestre' : 'Nuevo Semestre' }}
                    </h2>
                    <button @click="closeModal" :disabled="isSaving"
                        class="text-gray-400 hover:text-gray-600 p-1.5 hover:bg-gray-50 rounded-lg transition-colors disabled:opacity-50">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Nombre del Semestre Académico
                        </label>
                        <input v-model="formData.nombre" type="text" required placeholder="Ej: 2026-I o 2026-II"
                            :disabled="isSaving"
                            class="w-full px-3 py-2 bg-white border text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 text-sm transition-all"
                            :class="validationErrors?.nombre ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-200 focus:ring-blue-500 focus:border-blue-500'" />
                        <p v-if="validationErrors?.nombre" class="text-xs text-red-500 mt-1 block">
                            {{ validationErrors.nombre[0] }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Fecha de Inicio
                            </label>
                            <input v-model="formData.fecha_inicio" type="date" required :disabled="isSaving"
                                class="w-full px-3 py-2 bg-white border text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 text-sm transition-all"
                                :class="validationErrors?.fecha_inicio ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-200 focus:ring-blue-500 focus:border-blue-500'" />
                            <p v-if="validationErrors?.fecha_inicio" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.fecha_inicio[0] }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Fecha de Fin
                            </label>
                            <input v-model="formData.fecha_fin" type="date" required :disabled="isSaving"
                                class="w-full px-3 py-2 bg-white border text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 text-sm transition-all"
                                :class="validationErrors?.fecha_fin ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-200 focus:ring-blue-500 focus:border-blue-500'" />
                            <p v-if="validationErrors?.fecha_fin" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.fecha_fin[0] }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 -mx-6 -mb-6 p-4">
                        <button type="button" @click="closeModal" :disabled="isSaving"
                            class="px-4 py-2 border border-gray-200 text-gray-700 hover:bg-gray-50 font-medium rounded-lg text-sm transition-colors disabled:opacity-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSaving"
                            class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm gap-2 transition-all text-sm disabled:opacity-70 min-w-30">
                            <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
                            <span>{{ isSaving ? 'Guardando...' : (isEditing ? 'Guardar Cambios' : 'Crear Semestre')
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

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { X, BookOpen, Loader2 } from '@lucide/vue';
import type { Materia, MateriaFormData } from '@/types/materia';

const props = defineProps<{
    modelValue: boolean;
    materia: Materia | null;
    isSaving: boolean;
    carreras: { id: number; nombre: string; }[];
    semestres: { id: number; nombre: string; }[];
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'save', data: MateriaFormData): void;
}>();

const formData = ref<MateriaFormData>({
    carrera_id: null,
    semestre_academico_id: null,
    codigo: '',
    nombre: '',
    creditos: null
});

const isEditing = computed(() => !!props.materia);

// Sincroniza y limpia los campos reactivos del formulario cada vez que el modal cambia su estado de visibilidad
watch(() => props.modelValue, (isOpen) => {
    if (isOpen) {
        if (props.materia) {
            formData.value = {
                carrera_id: props.materia.carrera_id,
                semestre_academico_id: props.materia.semestre_academico_id,
                codigo: props.materia.codigo,
                nombre: props.materia.nombre,
                creditos: typeof props.materia.creditos === 'number' ? props.materia.creditos : null
            };
        } else {
            formData.value = {
                carrera_id: null,
                semestre_academico_id: null,
                codigo: '',
                nombre: '',
                creditos: null
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
        ...formData.value,
        codigo: formData.value.codigo.toUpperCase()
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
                        <BookOpen class="w-5 h-5 text-blue-600" />
                        {{ isEditing ? 'Editar Materia' : 'Nueva Materia' }}
                    </h2>
                    <button @click="closeModal" :disabled="isSaving"
                        class="text-gray-400 hover:text-gray-600 p-1.5 hover:bg-gray-50 rounded-lg transition-colors disabled:opacity-50">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Carrera Asociada
                        </label>
                        <select v-model="formData.carrera_id" required :disabled="isSaving"
                            class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all">
                            <option :value="null" disabled>Selecciona una carrera...</option>
                            <option v-for="carrera in carreras" :key="carrera.id" :value="carrera.id">
                                {{ carrera.nombre }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Ciclo/Semestre Académico
                        </label>
                        <select v-model="formData.semestre_academico_id" required :disabled="isSaving"
                            class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all">
                            <option :value="null" disabled>Selecciona un ciclo académico...</option>
                            <option v-for="semestre in semestres" :key="semestre.id" :value="semestre.id">
                                {{ semestre.nombre }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Código de la Materia
                        </label>
                        <input v-model="formData.codigo" type="text" required placeholder="Ej: MAT-101"
                            :disabled="isSaving"
                            class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all uppercase" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Nombre de la Materia
                        </label>
                        <input v-model="formData.nombre" type="text" required placeholder="Ej: Álgebra Lineal"
                            :disabled="isSaving"
                            class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Número de Créditos
                        </label>
                        <input v-model="formData.creditos" type="number" placeholder="Ej: 4" :disabled="isSaving" min="0"
                            class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all" />
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 -mx-6 -mb-6 p-4">
                        <button type="button" @click="closeModal" :disabled="isSaving"
                            class="px-4 py-2 border border-gray-200 text-gray-700 hover:bg-gray-50 font-medium rounded-lg text-sm transition-colors disabled:opacity-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSaving"
                            class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm gap-2 transition-all text-sm disabled:opacity-70 min-w-30">
                            <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
                            <span>{{ isSaving ? 'Guardando...' : (isEditing ? 'Guardar Cambios' : 'Crear Materia')
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

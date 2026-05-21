<script setup lang="ts">
import { ref, watch } from 'vue';
import { X, Loader2, Layers } from '@lucide/vue';
import type { Semestre, SemestreFormData } from '@/types/Semestre';

const props = defineProps<{
    modelValue: boolean;
    semestre: Semestre | null;
    isSaving: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'save', formData: SemestreFormData): void;
}>();

// Estado reactivo local del input
const formData = ref<SemestreFormData>({ nombre: '' });

// Sincroniza el estado al abrir para creación o edición
watch(() => props.modelValue, (isOpen) => {
    if (isOpen) {
        formData.value.nombre = props.semestre ? props.semestre.nombre : '';
    }
});

const closeModal = () => {
    if (!props.isSaving) emit('update:modelValue', false);
};

const handleSubmit = () => {
    if (!formData.value.nombre.trim()) return;
    emit('save', { nombre: formData.value.nombre.trim() });
};
</script>

<template>
    <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100"
        leave-to-class="opacity-0">
        <div v-if="modelValue"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs">
            <div
                class="bg-white rounded-xl shadow-xl border border-gray-100 max-w-md w-full overflow-hidden transform transition-all">

                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <div class="flex items-center gap-2">
                        <Layers class="w-5 h-5 text-emerald-600" />
                        <h3 class="text-base font-bold text-gray-900">
                            {{ semestre ? 'Modificar Semestre Catálogo' : 'Crear Semestre Maestro' }}
                        </h3>
                    </div>
                    <button type="button" @click="closeModal" :disabled="isSaving"
                        class="text-gray-400 hover:text-gray-600 rounded-lg p-1 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Nombre del Período /
                            Ciclo</label>
                        <input v-model="formData.nombre" type="text"
                            placeholder="Ej: 1er Semestre, 2do Semestre, Verano..." :disabled="isSaving"
                            class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-sm transition-all outline-none disabled:bg-gray-50"
                            required maxlength="100" />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" @click="closeModal" :disabled="isSaving"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSaving"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 shadow-sm transition-colors disabled:opacity-50">
                            <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
                            {{ semestre ? 'Guardar Cambios' : 'Registrar Semestre' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </Transition>
</template>
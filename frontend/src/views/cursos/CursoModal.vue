<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { X, Loader2, BookOpen } from '@lucide/vue';
import { carreraService } from '@/services/carreraService';
import { semestreService } from '@/services/academicService';
// import type { Curso, CursoFormData, Semestre } from '@/types/academic';
import type { Curso, CursoFormData } from '@/types/curso';
import type { Semestre } from '@/types/Semestre';
import type { Carrera } from '@/types/carrera';

const props = defineProps<{
    modelValue: boolean;
    curso: Curso | null;
    isSaving: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'save', formData: CursoFormData): void;
}>();

// Opciones para llenar los Selectors
const carrerasOptions = ref<Carrera[]>([]);
const semestresOptions = ref<Semestre[]>([]);
const isLoadingOptions = ref(false);

// Estado local del Formulario
const formData = ref<CursoFormData>({
    carrera_id: '',
    semestre_academico_id: ''
});

// Cargar catálogos maestros desde la API de Laravel
const loadDropdownOptions = async () => {
    try {
        isLoadingOptions.value = true;
        // Consultamos todas las carreras activas (puedes ajustar los parámetros según tu paginación)
        const resCarreras = await carreraService.getCarreras({ page: '1' });
        carrerasOptions.value = resCarreras.data;

        // Consultamos el catálogo de semestres base
        const resSemestres = await semestreService.getSemestres();
        // Soportamos si viene paginado o array directo
        semestresOptions.value = Array.isArray(resSemestres) ? resSemestres : (resSemestres as any).data || [];
    } catch (error) {
        console.error('Error al cargar opciones del formulario de cursos:', error);
    } finally {
        isLoadingOptions.value = false;
    }
};

// Sincronizar data cuando se abre para Edición o Creación
watch(() => props.modelValue, (isOpen) => {
    if (isOpen) {
        loadDropdownOptions();
        if (props.curso) {
            formData.value = {
                carrera_id: props.curso.carrera_id,
                semestre_academico_id: props.curso.semestre_academico_id
            };
        } else {
            formData.value = { carrera_id: '', semestre_academico_id: '' };
        }
    }
});

const closeModal = () => {
    if (!props.isSaving) emit('update:modelValue', false);
};

const handleSubmit = () => {
    if (!formData.value.carrera_id || !formData.value.semestre_academico_id) return;
    emit('save', { ...formData.value });
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
                        <BookOpen class="w-5 h-5 text-blue-600" />
                        <h3 class="text-base font-bold text-gray-900">
                            {{ curso ? 'Editar Asignación de Curso' : 'Asignar Nuevo Curso' }}
                        </h3>
                    </div>
                    <button @click="closeModal" :disabled="isSaving"
                        class="text-gray-400 hover:text-gray-600 rounded-lg p-1 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="handleSubmit" class="p-6 space-y-4">

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Carrera /
                            Programa</label>
                        <div class="relative">
                            <select v-model="formData.carrera_id" :disabled="isSaving || isLoadingOptions"
                                class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none disabled:bg-gray-50"
                                required>
                                <option value="">-- Selecciona la Carrera --</option>
                                <option v-for="carrera in carrerasOptions" :key="carrera.id" :value="carrera.id">
                                    [{{ carrera.codigo }}] {{ carrera.nombre }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Semestre Académico
                            Base</label>
                        <select v-model="formData.semestre_academico_id" :disabled="isSaving || isLoadingOptions"
                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none disabled:bg-gray-50"
                            required>
                            <option value="">-- Selecciona el Semestre Base --</option>
                            <option v-for="semestre in semestresOptions" :key="semestre.id" :value="semestre.id">
                                {{ semestre.nombre }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" @click="closeModal" :disabled="isSaving"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSaving || isLoadingOptions"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-xs transition-colors disabled:opacity-50">
                            <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
                            {{ curso ? 'Guardar Cambios' : 'Confirmar Asignación' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </Transition>
</template>
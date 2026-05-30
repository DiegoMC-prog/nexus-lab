<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { X, Calendar, Loader2 } from '@lucide/vue';
import type { Horario, HorarioFormData } from '@/types/horario';

const props = defineProps<{
    modelValue: boolean;
    horario: Horario | null;
    isSaving: boolean;
    laboratorios: { id: number; nombre: string; }[];
    docentes: { id: number; nombre: string; }[];
    grupos: { id: number; nombre: string; }[];
    validationErrors?: Record<string, string[]>;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'save', data: HorarioFormData): void;
}>();

const formData = ref<HorarioFormData>({
    laboratorio_id: null,
    docente_id: null,
    grupo_id: null,
    dia_semana: 1,
    hora_inicio: '',
    hora_fin: '',
    fecha_inicio: '',
    fecha_fin: ''
});

const isEditing = computed(() => !!props.horario);

const diasSemana = [
    { value: 1, label: 'Lunes' },
    { value: 2, label: 'Martes' },
    { value: 3, label: 'Miércoles' },
    { value: 4, label: 'Jueves' },
    { value: 5, label: 'Viernes' },
    { value: 6, label: 'Sábado' },
    { value: 7, label: 'Domingo' }
];

// Helper to format hours from HH:MM:SS to HH:MM if necessary
const formatTimeInput = (timeStr?: string): string => {
    if (!timeStr) return '';
    // If it is HH:MM:SS, slice it
    if (timeStr.length > 5) {
        return timeStr.slice(0, 5);
    }
    return timeStr;
};

// Sincroniza y limpia los campos del formulario
watch(() => props.modelValue, (isOpen) => {
    if (isOpen) {
        if (props.horario) {
            formData.value = {
                laboratorio_id: props.horario.laboratorio_id,
                docente_id: props.horario.docente_id,
                grupo_id: props.horario.grupo_id || null,
                dia_semana: props.horario.dia_semana || props.horario.dia_sema || 1,
                hora_inicio: formatTimeInput(props.horario.hora_inicio),
                hora_fin: formatTimeInput(props.horario.hora_fin),
                fecha_inicio: props.horario.fecha_inicio,
                fecha_fin: props.horario.fecha_fin
            };
        } else {
            formData.value = {
                laboratorio_id: null,
                docente_id: null,
                grupo_id: null,
                dia_semana: 1,
                hora_inicio: '',
                hora_fin: '',
                fecha_inicio: '',
                fecha_fin: ''
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
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm overflow-y-auto">
            <div
                class="bg-white rounded-xl shadow-xl border border-gray-100 max-w-lg w-full overflow-hidden my-8 transform transition-all scale-100 flex flex-col">

                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <Calendar class="w-5 h-5 text-blue-600" />
                        {{ isEditing ? 'Editar Horario Académico' : 'Nuevo Horario Académico' }}
                    </h2>
                    <button @click="closeModal" :disabled="isSaving"
                        class="text-gray-400 hover:text-gray-600 p-1.5 hover:bg-gray-50 rounded-lg transition-colors disabled:opacity-50">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="handleSubmit" class="p-6 space-y-4 overflow-y-auto max-h-[80vh]">
                    <!-- Alerta de solapamientos / errores generales del backend -->
                    <div v-if="validationErrors && Object.keys(validationErrors).length > 0" 
                        class="p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700 space-y-1">
                        <p class="font-bold">Por favor corrige los siguientes errores de solapamiento u ocupación:</p>
                        <ul class="list-disc pl-4 space-y-0.5">
                            <li v-for="(errs, key) in validationErrors" :key="key">
                                {{ errs.join(', ') }}
                            </li>
                        </ul>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Laboratorio
                            </label>
                            <select v-model="formData.laboratorio_id" required :disabled="isSaving"
                                class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.laboratorio_id }">
                                <option :value="null" disabled>Selecciona un laboratorio...</option>
                                <option v-for="lab in laboratorios" :key="lab.id" :value="lab.id">
                                    {{ lab.nombre }}
                                </option>
                            </select>
                            <p v-if="validationErrors?.laboratorio_id" class="text-xs text-red-600">
                                {{ validationErrors.laboratorio_id[0] }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Docente
                            </label>
                            <select v-model="formData.docente_id" required :disabled="isSaving"
                                class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.docente_id }">
                                <option :value="null" disabled>Selecciona un docente...</option>
                                <option v-for="doc in docentes" :key="doc.id" :value="doc.id">
                                    {{ doc.nombre }}
                                </option>
                            </select>
                            <p v-if="validationErrors?.docente_id" class="text-xs text-red-600">
                                {{ validationErrors.docente_id[0] }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Grupo Académico
                            </label>
                            <select v-model="formData.grupo_id" required :disabled="isSaving"
                                class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.grupo_id }">
                                <option :value="null" disabled>Selecciona un grupo...</option>
                                <option v-for="grupo in grupos" :key="grupo.id" :value="grupo.id">
                                    {{ grupo.nombre }}
                                </option>
                            </select>
                            <p v-if="validationErrors?.grupo_id" class="text-xs text-red-600">
                                {{ validationErrors.grupo_id[0] }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Día de la Semana
                            </label>
                            <select v-model="formData.dia_semana" required :disabled="isSaving"
                                class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.dia_semana }">
                                <option v-for="dia in diasSemana" :key="dia.value" :value="dia.value">
                                    {{ dia.label }}
                                </option>
                            </select>
                            <p v-if="validationErrors?.dia_semana" class="text-xs text-red-600">
                                {{ validationErrors.dia_semana[0] }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Hora de Inicio
                            </label>
                            <input v-model="formData.hora_inicio" type="time" required :disabled="isSaving"
                                class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.hora_inicio }" />
                            <p v-if="validationErrors?.hora_inicio" class="text-xs text-red-600">
                                {{ validationErrors.hora_inicio[0] }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Hora de Fin
                            </label>
                            <input v-model="formData.hora_fin" type="time" required :disabled="isSaving"
                                class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.hora_fin }" />
                            <p v-if="validationErrors?.hora_fin" class="text-xs text-red-600">
                                {{ validationErrors.hora_fin[0] }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Fecha de Inicio de Vigencia
                            </label>
                            <input v-model="formData.fecha_inicio" type="date" required :disabled="isSaving"
                                class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.fecha_inicio }" />
                            <p v-if="validationErrors?.fecha_inicio" class="text-xs text-red-600">
                                {{ validationErrors.fecha_inicio[0] }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Fecha de Fin de Vigencia
                            </label>
                            <input v-model="formData.fecha_fin" type="date" required :disabled="isSaving"
                                class="w-full px-3 py-2 bg-white border border-gray-200 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all"
                                :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.fecha_fin }" />
                            <p v-if="validationErrors?.fecha_fin" class="text-xs text-red-600">
                                {{ validationErrors.fecha_fin[0] }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 -mx-6 -mb-6 p-4 shrink-0">
                        <button type="button" @click="closeModal" :disabled="isSaving"
                            class="px-4 py-2 border border-gray-200 text-gray-700 hover:bg-gray-50 font-medium rounded-lg text-sm transition-colors disabled:opacity-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSaving"
                            class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm gap-2 transition-all text-sm disabled:opacity-70 min-w-30">
                            <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
                            <span>{{ isSaving ? 'Guardando...' : (isEditing ? 'Guardar Cambios' : 'Crear Horario')
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

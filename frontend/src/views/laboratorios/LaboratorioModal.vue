<script setup lang="ts">
import { ref, watch } from 'vue';
import { X, Loader2, Building2 } from '@lucide/vue';
import type { Laboratorio, LaboratorioFormData } from '@/types/laboratorio';

const props = withDefaults(defineProps<{
    show: boolean;
    lab: Laboratorio | null;
    loading?: boolean;
    validationErrors?: Record<string, string[]>;
}>(), {
    loading: false
});

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit', data: LaboratorioFormData): void;
}>();

const formData = ref<LaboratorioFormData>({
    nombre: '',
    pabellon: '',
    piso: '',
    activo: true,
    capacidad: 30
});

// Vigilar la apertura del modal para cargar o limpiar el formulario
watch(() => props.show, (isShown) => {
    if (isShown) {
        if (props.lab) {
            formData.value = {
                nombre: props.lab.nombre,
                pabellon: props.lab.pabellon,
                piso: props.lab.piso,
                activo: props.lab.activo,
                capacidad: props.lab.capacidad ?? 30
            };
        } else {
            formData.value = { nombre: '', pabellon: '', piso: '', activo: true, capacidad: 30 };
        }
    }
});

const handleSave = () => {
    emit('submit', { ...formData.value });
};
</script>

<template>
    <Transition enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 backdrop-blur-none" enter-to-class="opacity-100 backdrop-blur-md"
        leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 backdrop-blur-md"
        leave-to-class="opacity-0 backdrop-blur-none">
        <div v-if="show"
            class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div
                class="bg-white rounded-xl border border-gray-100 max-w-md w-full shadow-2xl relative overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-150">

                <div class="px-6 pt-6 pb-4 flex items-start justify-between border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div :class="lab ? 'bg-blue-50 text-blue-600' : 'bg-green-50 text-green-600'"
                            class="p-2.5 rounded-lg shrink-0">
                            <Building2 class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ lab ? 'Editar Laboratorio' : 'Nuevo Laboratorio' }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ lab ? 'Modifica la ubicación y disponibilidad del ambiente.' : 'Registra un nuevo espacio físico en el sistema.' }}
                            </p>
                        </div>
                    </div>
                    <button @click="emit('close')" :disabled="loading"
                        class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1.5 rounded-lg transition-colors cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Nombre /
                            Aula</label>
                        <input v-model="formData.nombre" type="text" placeholder="Ej. 202, Laboratorio de Redes"
                            :disabled="loading"
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-2xs transition-all placeholder:text-gray-400 disabled:bg-gray-50 disabled:text-gray-400"
                            :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.nombre }" />
                        <p v-if="validationErrors?.nombre" class="text-xs text-red-500 mt-1 block">
                            {{ validationErrors.nombre[0] }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Pabellón /
                            Bloque</label>
                        <input v-model="formData.pabellon" type="text" placeholder="Ej. Bloque B, Pabellón Norte"
                            :disabled="loading"
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-2xs transition-all placeholder:text-gray-400 disabled:bg-gray-50 disabled:text-gray-400"
                            :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.pabellon }" />
                        <p v-if="validationErrors?.pabellon" class="text-xs text-red-500 mt-1 block">
                            {{ validationErrors.pabellon[0] }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Piso</label>
                            <input v-model="formData.piso" type="text" placeholder="Ej. Piso 3" :disabled="loading"
                                class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-2xs transition-all placeholder:text-gray-400 disabled:bg-gray-50 disabled:text-gray-400"
                                :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.piso }" />
                            <p v-if="validationErrors?.piso" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.piso[0] }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Estado
                                Operativo</label>
                            <select v-model="formData.activo" :disabled="loading"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-2xs transition-all cursor-pointer disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed"
                                :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.activo }">
                                <option :value="true">Activo / Disponible</option>
                                <option :value="false">Inactivo / Mantenimiento</option>
                            </select>
                            <p v-if="validationErrors?.activo" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.activo[0] }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">
                            Aforo Máximo
                            <span class="normal-case text-gray-400 font-normal ml-1">(número de PCs)</span>
                        </label>
                        <div class="relative">
                            <input v-model.number="formData.capacidad" type="number" min="1" max="500"
                                :disabled="loading" placeholder="Ej. 30"
                                class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-2xs transition-all placeholder:text-gray-400 disabled:bg-gray-50 disabled:text-gray-400"
                                :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.capacidad }" />
                            <p v-if="validationErrors?.capacidad" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.capacidad[0] }}
                            </p>
                            <p v-if="lab?.estaciones_count !== undefined" class="text-xs text-gray-400 mt-1">
                                {{ lab.estaciones_count }} estaciones actualmente registradas.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2.5">
                    <button @click="emit('close')" :disabled="loading"
                        class="px-4 py-2 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-100 hover:border-gray-300 transition-all text-sm font-medium cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        Cancelar
                    </button>

                    <button @click="handleSave"
                        :disabled="!formData.nombre || !formData.pabellon || !formData.piso || !formData.capacidad || loading"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-all text-sm font-medium shadow-sm cursor-pointer flex items-center gap-2">
                        <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
                        {{ loading ? 'Guardando...' : (lab ? 'Guardar Cambios' : 'Crear Laboratorio') }}
                    </button>
                </div>

            </div>
        </div>
    </Transition>
</template>
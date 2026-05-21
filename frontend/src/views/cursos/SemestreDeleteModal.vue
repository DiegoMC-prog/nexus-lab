<script setup lang="ts">
import { AlertTriangle, Loader2 } from '@lucide/vue';
import type { Semestre } from '@/types/Semestre';

const props = defineProps<{
    modelValue: boolean;
    semestre: Semestre | null;
    isSaving: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'confirm'): void;
}>();

const closeModal = () => {
    if (!props.isSaving) emit('update:modelValue', false);
};
</script>

<template>
    <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100"
        leave-to-class="opacity-0">
        <div v-if="modelValue"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs">
            <div
                class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden transform transition-all border border-red-100">

                <div class="p-6 flex gap-4">
                    <div
                        class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-600 shrink-0">
                        <AlertTriangle class="w-5 h-5" />
                    </div>
                    <div class="space-y-1.5">
                        <h3 class="text-base font-bold text-gray-900">¿Eliminar del Catálogo Maestro?</h3>
                        <p class="text-sm text-gray-500 leading-relaxed" v-if="semestre">
                            ¿Estás seguro de que deseas eliminar el <span class="font-semibold text-gray-900">"{{
                                semestre.nombre }}"</span> permanentemente?
                        </p>
                        <p class="text-xs text-gray-400">
                            La base de datos denegará la acción si el semestre se encuentra asignado a una carrera
                            universitaria.
                        </p>
                    </div>
                </div>

                <div class="px-6 py-3 bg-gray-50 flex justify-end gap-2 border-t border-gray-100">
                    <button type="button" @click="closeModal" :disabled="isSaving"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50">
                        Cancelar
                    </button>
                    <button type="button" @click="emit('confirm')" :disabled="isSaving"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-sm transition-colors disabled:opacity-50">
                        <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
                        Eliminar del Catálogo
                    </button>
                </div>

            </div>
        </div>
    </Transition>
</template>
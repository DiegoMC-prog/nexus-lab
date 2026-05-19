<script setup lang="ts">
import { X, AlertTriangle } from '@lucide/vue';
import type { Laboratorio } from '@/types/laboratorio';

defineProps<{
    show: boolean;
    lab: Laboratorio | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'confirm'): void;
}>();
</script>

<template>
    <Transition enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 backdrop-blur-none" enter-to-class="opacity-100 backdrop-blur-md"
        leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 backdrop-blur-md"
        leave-to-class="opacity-0 backdrop-blur-none">
        <div v-if="show"
            class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div
                class="bg-white rounded-xl border border-gray-100 max-w-md w-full shadow-2xl relative overflow-hidden animate-in fade-in zoom-in-95 duration-150">

                <button @click="emit('close')"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1.5 rounded-lg transition-colors cursor-pointer">
                    <X class="w-4 h-4" />
                </button>

                <div class="p-6 flex gap-4 items-start pt-7">
                    <div class="bg-red-50 text-red-600 p-3 rounded-xl shrink-0 shadow-xs">
                        <AlertTriangle class="w-6 h-6" />
                    </div>
                    <div class="space-y-1.5">
                        <h3 class="text-lg font-semibold text-gray-900">Eliminar Laboratorio</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">
                            ¿Estás seguro de que deseas eliminar permanentemente el espacio de:
                            <span
                                class="text-gray-950 font-semibold block bg-gray-50 border border-gray-100 rounded-lg px-3 py-2 my-2 text-xs w-fit">
                                Laboratorio {{ lab?.nombre }} — {{ lab?.pabellon }} ({{ lab?.piso }})
                            </span>
                            Esta acción no se puede deshacer. Se desvincularán todas las estaciones de trabajo asignadas
                            a este ambiente de forma inmediata.
                        </p>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2.5">
                    <button @click="emit('close')"
                        class="px-4 py-2 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-100 hover:border-gray-300 transition-all text-sm font-medium cursor-pointer">
                        Cancelar
                    </button>
                    <button @click="emit('confirm')"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white rounded-lg transition-all text-sm font-medium shadow-xs cursor-pointer">
                        Eliminar Registro
                    </button>
                </div>

            </div>
        </div>
    </Transition>
</template>
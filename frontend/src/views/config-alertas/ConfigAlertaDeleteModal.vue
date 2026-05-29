<script setup lang="ts">
import { ref } from 'vue';
import { X, AlertTriangle, Loader2 } from '@lucide/vue';

defineProps<{
    show: boolean;
    config: any | null;
    isDeleting?: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'confirm'): void;
}>();
</script>

<template>
    <Transition 
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 backdrop-blur-none" 
        enter-to-class="opacity-100 backdrop-blur-md"
        leave-active-class="transition-all duration-200 ease-in" 
        leave-from-class="opacity-100 backdrop-blur-md"
        leave-to-class="opacity-0 backdrop-blur-none"
    >
        <div v-if="show" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl border border-gray-100 max-w-md w-full shadow-2xl relative overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-150">
                
                <div class="px-6 pt-6 pb-4 flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="bg-red-50 text-red-600 p-2.5 rounded-lg shrink-0 border border-red-100">
                            <AlertTriangle class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Eliminar Regla de Alerta</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Esta acción no se puede deshacer.</p>
                        </div>
                    </div>
                    <button @click="emit('close')" :disabled="isDeleting"
                        class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1.5 rounded-lg transition-colors cursor-pointer disabled:opacity-30">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-6">
                    <p class="text-sm text-gray-600 leading-relaxed">
                        ¿Está absolutamente seguro de que desea eliminar la regla de monitoreo 
                        <span class="font-bold text-gray-900">"{{ config?.metrica }} {{ config?.operador }} {{ config?.valor_umbral }}"</span>? 
                        El sistema dejará de disparar alarmas reactivas para este umbral de rendimiento.
                    </p>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2.5">
                    <button @click="emit('close')" :disabled="isDeleting"
                        class="px-4 py-2 border border-gray-200 text-gray-700 bg-white rounded-lg hover:bg-gray-100 hover:border-gray-355 transition-all text-xs font-medium cursor-pointer disabled:opacity-50">
                        Cancelar
                    </button>
                    
                    <button @click="emit('confirm')" :disabled="isDeleting"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 active:bg-red-800 disabled:opacity-50 text-white rounded-lg transition-all text-xs font-medium shadow-sm cursor-pointer flex items-center gap-1.5">
                        <Loader2 v-if="isDeleting" class="w-3.5 h-3.5 animate-spin" />
                        <span>{{ isDeleting ? 'Eliminando...' : 'Eliminar Regla' }}</span>
                    </button>
                </div>

            </div>
        </div>
    </Transition>
</template>

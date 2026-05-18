<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue';

// 1. Definimos las Props genéricas que necesita cualquier paginación de Laravel
const props = defineProps<{
    modelValue: number; // Página actual (v-model por defecto en Vue 3)
    lastPage: number;   // Última página disponible
    total: number;      // Cantidad total de registros de la BD
}>();

// 2. Definimos el evento de actualización para que funcione el v-model bidireccional
const emit = defineEmits<{
    (e: 'update:modelValue', page: number): void;
}>();

// Función intermedia segura para evitar desbordamientos de páginas
const changePage = (page: number) => {
    if (page >= 1 && page <= props.lastPage) {
        emit('update:modelValue', page);
    }
};
</script>

<template>
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <div class="flex-1 flex justify-between sm:hidden">
            <button :disabled="modelValue === 1" @click="changePage(modelValue - 1)"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                Anterior
            </button>
            <button :disabled="modelValue === lastPage" @click="changePage(modelValue + 1)"
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                Siguiente
            </button>
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Mostrando página <span class="font-medium">{{ modelValue }}</span> de
                    <span class="font-medium">{{ lastPage }}</span> (<span class="font-medium">{{ total }}</span>
                    registros totales)
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-xs -space-x-px" aria-label="Pagination">
                    <button :disabled="modelValue === 1" @click="changePage(modelValue - 1)"
                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">
                        <span class="sr-only">Anterior</span>
                        <ChevronLeft class="h-4 w-4" />
                    </button>

                    <button v-for="page in lastPage" :key="page" @click="changePage(page)" :class="[
                        page === modelValue
                            ? 'z-10 bg-blue-50 border-blue-500 text-blue-600 font-semibold'
                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium cursor-pointer'
                    ]">
                        {{ page }}
                    </button>

                    <button :disabled="modelValue === lastPage" @click="changePage(modelValue + 1)"
                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">
                        <span class="sr-only">Siguiente</span>
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </nav>
            </div>
        </div>
    </div>
</template>
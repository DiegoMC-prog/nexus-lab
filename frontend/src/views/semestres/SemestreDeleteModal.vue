<script setup lang="ts">
import { AlertTriangle, Loader2 } from '@lucide/vue'
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
  if (!props.isSaving) {
    emit('update:modelValue', false);
  }
};

const confirmDelete = () => {
  emit('confirm');
};
</script>

<template>
  <Transition name="modal">
    <div 
      v-if="modelValue" 
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white rounded-xl shadow-xl border border-gray-100 max-w-md w-full overflow-hidden transform transition-all scale-100">
        
        <div class="p-6 flex gap-4">
          <div class="w-10 h-10 rounded-full bg-red-50 border border-red-200 flex items-center justify-center shrink-0">
            <AlertTriangle class="w-5 h-5 text-red-600" />
          </div>
          <div class="space-y-2">
            <h3 class="text-lg font-bold text-gray-900">¿Eliminar Semestre Académico?</h3>
            <p class="text-sm text-gray-500">
              ¿Estás seguro de que deseas eliminar permanentemente este semestre académico del sistema? Esta acción no se puede deshacer.
            </p>
            
            <div v-if="semestre" class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-xs font-medium space-y-1">
              <div class="text-gray-950 font-bold text-sm">{{ semestre.nombre }}</div>
              <div class="text-gray-500">ID del Registro: {{ semestre.id }}</div>
            </div>
          </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
          <button 
            type="button"
            @click="closeModal"
            :disabled="isSaving"
            class="px-4 py-2 border border-gray-200 text-gray-700 hover:bg-gray-50 font-medium rounded-lg text-sm transition-colors disabled:opacity-50"
          >
            Cancelar
          </button>
          <button 
            type="button"
            @click="confirmDelete"
            :disabled="isSaving"
            class="inline-flex items-center justify-center bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm gap-2 transition-all text-sm disabled:opacity-70 min-w-25"
          >
            <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
            <span>{{ isSaving ? 'Eliminando...' : 'Eliminar' }}</span>
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.25s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
</style>

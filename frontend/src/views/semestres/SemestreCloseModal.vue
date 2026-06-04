<script setup lang="ts">
import { ref } from 'vue';
import { AlertCircle, X, Lock } from '@lucide/vue';

const props = defineProps<{
    isOpen: boolean;
    semestreNombre: string;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'confirm'): void;
}>();

const isClosing = ref(false);

const handleConfirm = async () => {
    isClosing.value = true;
    try {
        emit('confirm');
    } finally {
        isClosing.value = false;
    }
};

const handleClose = () => {
    if (!isClosing.value) {
        emit('close');
    }
};
</script>

<template>
    <Transition name="modal">
        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0 bg-black/50 backdrop-blur-sm" @click="handleClose">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md relative z-10 overflow-hidden transform transition-all scale-100"
                @click.stop>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                            <Lock class="w-6 h-6 text-red-600" />
                        </div>
                        <button @click="handleClose" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <h3 class="text-xl font-bold text-gray-900 mb-2">
                        Cerrar Semestre
                    </h3>

                    <p class="text-gray-600 mb-4">
                        ¿Estás seguro que deseas cerrar el semestre académico <strong>{{ semestreNombre }}</strong>?
                    </p>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex gap-3 text-sm text-yellow-800 mb-6">
                        <AlertCircle class="w-5 h-5 flex-shrink-0" />
                        <p>
                            Esta acción bloqueará definitivamente la edición del semestre y de todos sus registros asociados (Materias, Grupos y Horarios).
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button @click="handleClose" :disabled="isClosing"
                            class="flex-1 px-4 py-2 border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors disabled:opacity-50 font-medium">
                            Cancelar
                        </button>
                        <button @click="handleConfirm" :disabled="isClosing"
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors disabled:opacity-50 font-medium flex items-center justify-center gap-2">
                            <svg v-if="isClosing" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <Lock v-else class="w-4 h-4" />
                            Sí, cerrar semestre
                        </button>
                    </div>
                </div>
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

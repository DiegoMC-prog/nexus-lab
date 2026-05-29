<script setup lang="ts">
import { ref, watch } from 'vue';
import { X, Loader2, Terminal } from '@lucide/vue';
import type { ComandoFormData } from '@/services/comandoService';

const props = withDefaults(defineProps<{
    show: boolean;
    comando: any | null;
    loading?: boolean;
    validationErrors?: Record<string, string[]>;
}>(), {
    loading: false
});

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit', data: ComandoFormData): void;
}>();

const formData = ref<ComandoFormData>({
    nombre: '',
    slug: '',
    tipo: 'sistema',
    require_auth: false
});

// Auto-generación de slug a partir del nombre
watch(() => formData.value.nombre, (newName) => {
    if (!props.comando) {
        formData.value.slug = newName
            .toLowerCase()
            .trim()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "") // Remueve acentos
            .replace(/[^a-z0-9\s-_]/g, '') // Remueve caracteres especiales
            .replace(/[\s_]+/g, '-') // Reemplaza espacios/guiones bajos con un solo guión medio
            .replace(/^-+|-+$/g, ''); // Limpia guiones en los extremos
    }
});

watch(() => props.show, (isShown) => {
    if (isShown) {
        if (props.comando) {
            formData.value = {
                nombre: props.comando.nombre,
                slug: props.comando.slug,
                tipo: props.comando.tipo,
                require_auth: Boolean(props.comando.require_auth)
            };
        } else {
            formData.value = { nombre: '', slug: '', tipo: 'sistema', require_auth: false };
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
                        <div :class="comando ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-green-50 text-green-600 border border-green-100'"
                            class="p-2.5 rounded-lg shrink-0">
                            <Terminal class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ comando ? 'Editar Comando' : 'Nueva Plantilla de Comando' }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ comando ? 'Modifica la estructura del comando remoto.' : 'Registra un nuevo comando ejecutable en los agentes.' }}
                            </p>
                        </div>
                    </div>
                    <button @click="emit('close')" :disabled="loading"
                        class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1.5 rounded-lg transition-colors cursor-pointer disabled:opacity-30">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Nombre -->
                    <div class="space-y-1.5">
                        <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Nombre del Comando</label>
                        <input v-model="formData.nombre" type="text" placeholder="Ej. Apagar Equipo, Reiniciar Agente"
                            :disabled="loading"
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-2xs transition-all placeholder:text-gray-400 disabled:bg-gray-50 disabled:text-gray-400"
                            :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.nombre }" />
                        <p v-if="validationErrors?.nombre" class="text-xs text-red-500 mt-1 block">
                            {{ validationErrors.nombre[0] }}
                        </p>
                    </div>

                    <!-- Slug -->
                    <div class="space-y-1.5">
                        <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Slug de Ejecución</label>
                        <input v-model="formData.slug" type="text" placeholder="Ej. apagar-equipo"
                            :disabled="loading"
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-2xs font-mono transition-all placeholder:text-gray-400 disabled:bg-gray-50 disabled:text-gray-400"
                            :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.slug }" />
                        <p v-if="validationErrors?.slug" class="text-xs text-red-500 mt-1 block">
                            {{ validationErrors.slug[0] }}
                        </p>
                    </div>

                    <!-- Tipo y Requiere Auth -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Tipo</label>
                            <select v-model="formData.tipo" :disabled="loading"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-2xs transition-all cursor-pointer">
                                <option value="sistema">Sistema</option>
                                <option value="energia">Energía</option>
                                <option value="kiosko">Modo Kiosko</option>
                                <option value="personalizado">Personalizado</option>
                            </select>
                            <p v-if="validationErrors?.tipo" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.tipo[0] }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Autenticación</label>
                            <select v-model="formData.require_auth" :disabled="loading"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-2xs transition-all cursor-pointer">
                                <option :value="false">Libre Ejecución</option>
                                <option :value="true">Requiere Contraseña</option>
                            </select>
                            <p v-if="validationErrors?.require_auth" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.require_auth[0] }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2.5">
                    <button @click="emit('close')" :disabled="loading"
                        class="px-4 py-2 border border-gray-200 text-gray-700 bg-white rounded-lg hover:bg-gray-100 hover:border-gray-300 transition-all text-xs font-medium cursor-pointer disabled:opacity-50">
                        Cancelar
                    </button>

                    <button @click="handleSave"
                        :disabled="!formData.nombre || !formData.slug || loading"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-all text-xs font-medium shadow-sm cursor-pointer flex items-center gap-2">
                        <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
                        {{ loading ? 'Guardando...' : (comando ? 'Guardar Cambios' : 'Crear Comando') }}
                    </button>
                </div>

            </div>
        </div>
    </Transition>
</template>

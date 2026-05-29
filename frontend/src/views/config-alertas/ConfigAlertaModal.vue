<script setup lang="ts">
import { ref, watch } from 'vue';
import { X, Loader2, Sliders } from '@lucide/vue';
import type { ConfigAlertaFormData } from '@/services/configAlertaService';

const props = withDefaults(defineProps<{
    show: boolean;
    config: any | null;
    loading?: boolean;
    validationErrors?: Record<string, string[]>;
}>(), {
    loading: false
});

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit', data: ConfigAlertaFormData): void;
}>();

const formData = ref<ConfigAlertaFormData>({
    metrica: 'carga_cpu',
    operador: '>',
    valor_umbral: 80,
    severidad: 'alta',
    activo: true
});

watch(() => props.show, (isShown) => {
    if (isShown) {
        if (props.config) {
            formData.value = {
                metrica: props.config.metrica,
                operador: props.config.operador,
                valor_umbral: Number(props.config.valor_umbral),
                severidad: props.config.severidad,
                activo: Boolean(props.config.activo)
            };
        } else {
            formData.value = { metrica: 'carga_cpu', operador: '>', valor_umbral: 80, severidad: 'alta', activo: true };
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
                        <div :class="config ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-green-50 text-green-600 border border-green-100'"
                            class="p-2.5 rounded-lg shrink-0">
                            <Sliders class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ config ? 'Editar Regla de Monitoreo' : 'Nueva Regla de Monitoreo' }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Define umbrales de telemetría y alarmas reactivas.
                            </p>
                        </div>
                    </div>
                    <button @click="emit('close')" :disabled="loading"
                        class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1.5 rounded-lg transition-colors cursor-pointer disabled:opacity-30">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Metrica -->
                    <div class="space-y-1.5">
                        <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Métrica de Telemetría</label>
                        <select v-model="formData.metrica" :disabled="loading"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-2xs transition-all cursor-pointer">
                            <option value="carga_cpu">Carga de CPU (%)</option>
                            <option value="uso_ram_mb">Uso de RAM (MB)</option>
                            <option value="temp_cpu">Temperatura de CPU (°C)</option>
                            <option value="uso_disco">Uso de Disco (%)</option>
                            <option value="latencia_red">Latencia de Red / Ping (ms)</option>
                        </select>
                        <p v-if="validationErrors?.metrica" class="text-xs text-red-500 mt-1 block">
                            {{ validationErrors.metrica[0] }}
                        </p>
                    </div>

                    <!-- Operador y Umbral -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Operador</label>
                            <select v-model="formData.operador" :disabled="loading"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-2xs transition-all cursor-pointer">
                                <option value=">">Mayor que (>)</option>
                                <option value=">=">Mayor o igual que (>=)</option>
                                <option value="<">Menor que (<)</option>
                                <option value="<=">Menor o igual que (<=)</option>
                                <option value="==">Exactamente igual (==)</option>
                            </select>
                            <p v-if="validationErrors?.operador" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.operador[0] }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Valor Umbral</label>
                            <input v-model.number="formData.valor_umbral" type="number" step="any" placeholder="Ej. 80"
                                :disabled="loading"
                                class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-2xs transition-all placeholder:text-gray-400 disabled:bg-gray-50 disabled:text-gray-400"
                                :class="{ 'border-red-500 focus:ring-red-500': validationErrors?.valor_umbral }" />
                            <p v-if="validationErrors?.valor_umbral" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.valor_umbral[0] }}
                            </p>
                        </div>
                    </div>

                    <!-- Severidad y Activo -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Severidad</label>
                            <select v-model="formData.severidad" :disabled="loading"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-2xs transition-all cursor-pointer">
                                <option value="baja">Baja (Info)</option>
                                <option value="media">Media (Advertencia)</option>
                                <option value="alta">Alta (Crítica)</option>
                                <option value="critica">Crítica (Peligro)</option>
                            </select>
                            <p v-if="validationErrors?.severidad" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.severidad[0] }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-gray-700 text-xs font-semibold uppercase tracking-wider">Estado Regla</label>
                            <select v-model="formData.activo" :disabled="loading"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-hidden focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 text-gray-900 bg-white text-sm shadow-2xs transition-all cursor-pointer">
                                <option :value="true">Monitoreo Activo</option>
                                <option :value="false">Desactivada</option>
                            </select>
                            <p v-if="validationErrors?.activo" class="text-xs text-red-500 mt-1 block">
                                {{ validationErrors.activo[0] }}
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
                        :disabled="formData.valor_umbral === undefined || formData.valor_umbral === null || loading"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-all text-xs font-medium shadow-sm cursor-pointer flex items-center gap-2">
                        <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
                        {{ loading ? 'Guardando...' : (config ? 'Guardar Cambios' : 'Crear Regla') }}
                    </button>
                </div>

            </div>
        </div>
    </Transition>
</template>

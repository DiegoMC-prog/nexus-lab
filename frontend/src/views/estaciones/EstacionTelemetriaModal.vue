<script setup lang="ts">
import { ref, watch } from 'vue';
import { 
    X, Loader2, Activity, Cpu, Database, 
    Thermometer, Wifi, Trash2, RefreshCw 
} from '@lucide/vue';
import { telemetriaService } from '@/services/telemetriaService';
import BasePagination from '@/components/BasePagination.vue';

const props = defineProps<{
    show: boolean;
    estacion: any | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

// --- ESTADOS REACTIVOS ---
const logs = ref<any[]>([]);
const isLoading = ref(false);
const isDeletingId = ref<number | null>(null);

// Paginación
const currentPage = ref(1);
const lastPage = ref(1);
const totalLogs = ref(0);

// --- MÉTODOS DE DATOS ---
const fetchLogs = async () => {
    if (!props.estacion) return;
    isLoading.value = true;
    try {
        const response = await telemetriaService.getTelemetryLogs({
            estacion_id: props.estacion.id,
            page: currentPage.value
        });
        logs.value = response.data || [];
        lastPage.value = response.last_page || 1;
        totalLogs.value = response.total || 0;
    } catch (error) {
        console.error('Error al cargar logs de telemetría:', error);
    } finally {
        isLoading.value = false;
    }
};

const handleDeleteLog = async (logId: number) => {
    isDeletingId.value = logId;
    try {
        await telemetriaService.eliminarTelemetryLog(logId);
        // Recargar logs
        if (logs.value.length === 1 && currentPage.value > 1) {
            currentPage.value--;
        } else {
            await fetchLogs();
        }
    } catch (error) {
        console.error('Error al eliminar log de telemetría:', error);
    } finally {
        isDeletingId.value = null;
    }
};

// Formateo de fecha
const formatDateTime = (dateString: string) => {
    try {
        const date = new Date(dateString);
        return date.toLocaleString();
    } catch (e) {
        return dateString;
    }
};

// Formateo de RAM
const formatRam = (ramMb: number) => {
    if (ramMb >= 1024) {
        return `${(ramMb / 1024).toFixed(2)} GB`;
    }
    return `${ramMb} MB`;
};

// Watchers
watch(() => props.show, (isShown) => {
    if (isShown) {
        currentPage.value = 1;
        fetchLogs();
    }
});

watch(currentPage, () => {
    fetchLogs();
});
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
            <div class="bg-white rounded-xl border border-gray-150 max-w-4xl w-full shadow-2xl relative overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200 max-h-[90vh]">
                
                <!-- Encabezado -->
                <div class="px-6 pt-6 pb-4 flex items-start justify-between border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-50 text-blue-600 p-2.5 rounded-lg shrink-0 border border-blue-100">
                            <Activity class="w-5 h-5 animate-pulse" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                Historial de Telemetría
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Estación: <span class="font-bold text-blue-600">{{ estacion?.hostname }}</span> (IP: {{ estacion?.direccion_ip }})
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <button @click="fetchLogs" :disabled="isLoading"
                            class="text-gray-500 hover:text-blue-600 hover:bg-slate-50 p-2 rounded-lg border border-gray-200 transition-colors cursor-pointer disabled:opacity-50">
                            <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoading }" />
                        </button>
                        <button @click="emit('close')"
                            class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-lg transition-colors cursor-pointer">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Cuerpo del Reporte -->
                <div class="p-6 overflow-y-auto flex-1 relative min-h-[350px]">
                    <!-- Loading Overlay -->
                    <div v-if="isLoading && logs.length === 0" class="absolute inset-0 bg-white/60 z-10 flex items-center justify-center">
                        <div class="flex items-center gap-3 bg-white p-4 rounded-xl border border-slate-100 shadow-md">
                            <Loader2 class="w-5 h-5 text-blue-600 animate-spin" />
                            <span class="text-sm font-medium text-gray-700">Analizando telemetría...</span>
                        </div>
                    </div>

                    <!-- Tabla de Logs -->
                    <div v-if="logs.length === 0 && !isLoading" class="flex flex-col items-center justify-center py-20 text-center space-y-3">
                        <Activity class="w-12 h-12 text-slate-300" />
                        <p class="font-medium text-gray-700 text-sm">No hay registros de rendimiento</p>
                        <p class="text-xs text-gray-400 max-w-xs leading-relaxed">
                            Esta estación de trabajo aún no ha reportado logs de telemetría a la base de datos de NexusLab.
                        </p>
                    </div>

                    <div v-else class="space-y-4">
                        <div class="bg-white rounded-lg border border-gray-200 shadow-xs overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-gray-200 font-semibold text-gray-500 uppercase tracking-wider text-2xs">
                                            <th class="px-5 py-3">Fecha y Hora</th>
                                            <th class="px-5 py-3">Carga CPU</th>
                                            <th class="px-5 py-3">Uso RAM</th>
                                            <th class="px-5 py-3">Temp CPU</th>
                                            <th class="px-5 py-3">Uso Disco</th>
                                            <th class="px-5 py-3">Ping Red</th>
                                            <th class="px-5 py-3 text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-150 font-mono text-gray-700">
                                        <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50/50 transition-colors">
                                            <!-- Fecha -->
                                            <td class="px-5 py-3 font-sans text-gray-600 font-medium">
                                                {{ formatDateTime(log.created_at) }}
                                            </td>

                                            <!-- CPU -->
                                            <td class="px-5 py-3">
                                                <div class="space-y-1">
                                                    <div class="flex items-center justify-between font-bold text-slate-800">
                                                        <span>{{ log.carga_cpu }}%</span>
                                                    </div>
                                                    <!-- Progress Bar -->
                                                    <div class="w-24 bg-slate-100 rounded-full h-1.5 overflow-hidden border border-slate-200">
                                                        <div 
                                                            class="h-full rounded-full"
                                                            :class="[
                                                                log.carga_cpu >= 85 ? 'bg-rose-500' :
                                                                log.carga_cpu >= 60 ? 'bg-amber-500' : 'bg-emerald-500'
                                                            ]"
                                                            :style="{ width: `${log.carga_cpu}%` }"
                                                        ></div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- RAM -->
                                            <td class="px-5 py-3 text-slate-800 font-semibold">
                                                <div class="flex items-center gap-1">
                                                    <Database class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                                    <span>{{ formatRam(log.uso_ram_mb) }}</span>
                                                </div>
                                            </td>

                                            <!-- Temp -->
                                            <td class="px-5 py-3 font-semibold">
                                                <div class="flex items-center gap-1"
                                                    :class="[
                                                        log.temp_cpu >= 75 ? 'text-red-600' :
                                                        log.temp_cpu >= 60 ? 'text-amber-600' : 'text-slate-800'
                                                    ]"
                                                >
                                                    <Thermometer class="w-3.5 h-3.5 shrink-0" />
                                                    <span>{{ log.temp_cpu }}°C</span>
                                                </div>
                                            </td>

                                            <!-- Disco -->
                                            <td class="px-5 py-3">
                                                <div class="space-y-1">
                                                    <div class="flex items-center justify-between font-bold text-slate-800">
                                                        <span>{{ log.uso_disco }}%</span>
                                                    </div>
                                                    <div class="w-24 bg-slate-100 rounded-full h-1.5 overflow-hidden border border-slate-200">
                                                        <div 
                                                            class="h-full rounded-full"
                                                            :class="[
                                                                log.uso_disco >= 90 ? 'bg-rose-500' :
                                                                log.uso_disco >= 75 ? 'bg-amber-500' : 'bg-emerald-500'
                                                            ]"
                                                            :style="{ width: `${log.uso_disco}%` }"
                                                        ></div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Latencia -->
                                            <td class="px-5 py-3">
                                                <span :class="[
                                                    'inline-flex items-center gap-0.5 font-bold px-1.5 py-0.5 rounded border text-[10px]',
                                                    log.latencia_red >= 150 ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                                    log.latencia_red >= 60 ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                                    'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                ]">
                                                    <Wifi class="w-3 h-3 shrink-0" />
                                                    {{ log.latencia_red }} ms
                                                </span>
                                            </td>

                                            <!-- Acciones -->
                                            <td class="px-5 py-3 text-right">
                                                <button 
                                                    @click="handleDeleteLog(log.id)"
                                                    :disabled="isDeletingId === log.id"
                                                    class="text-red-500 hover:text-red-700 hover:bg-red-50 p-1.5 rounded transition-all cursor-pointer disabled:opacity-50"
                                                >
                                                    <Loader2 v-if="isDeletingId === log.id" class="w-3.5 h-3.5 animate-spin" />
                                                    <Trash2 v-else class="w-3.5 h-3.5" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Paginación -->
                        <div class="pt-2">
                            <BasePagination v-model="currentPage" :last-page="lastPage" :total="totalLogs" />
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-150 flex justify-end">
                    <button @click="emit('close')"
                        class="px-4 py-2 border border-gray-200 text-gray-700 bg-white rounded-lg hover:bg-gray-100 hover:border-gray-300 transition-all text-xs font-medium cursor-pointer">
                        Cerrar Historial
                    </button>
                </div>

            </div>
        </div>
    </Transition>
</template>

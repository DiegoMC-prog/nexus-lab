<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { 
    Search, Trash2, CheckCircle2, AlertOctagon, Loader2, 
    RefreshCw, ShieldAlert, Cpu, Database, Thermometer, Wifi 
} from '@lucide/vue';
import { alertaService } from '@/services/alertaService';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

// Componentes del Sistema
import BasePagination from '@/components/BasePagination.vue';

// --- ESTADOS REACTIVOS ---
const alertas = ref<any[]>([]);
const isLoading = ref(true);
const isSavingId = ref<number | null>(null);

// Filtros
const searchTerm = ref('');
const filterEstado = ref('pendiente'); // Por defecto ver las pendientes de atención

// Paginación
const currentPage = ref(1);
const lastPage = ref(1);
const totalAlertas = ref(0);

// --- ACCIONES DE DATOS ---
const fetchAlertas = async () => {
    isLoading.value = true;
    try {
        const response = await alertaService.getAlertas({
            page: currentPage.value,
            search: searchTerm.value || undefined,
            estado: filterEstado.value === 'all' ? undefined : filterEstado.value
        });
        alertas.value = response.data || [];
        currentPage.value = response.current_page || 1;
        lastPage.value = response.last_page || 1;
        totalAlertas.value = response.total || 0;
    } catch (error) {
        console.error('Error al cargar alertas:', error);
    } finally {
        isLoading.value = false;
    }
};

let debounceTimeout: ReturnType<typeof setTimeout>;
watch(searchTerm, () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchAlertas();
    }, 450);
});

watch(filterEstado, () => {
    currentPage.value = 1;
    fetchAlertas();
});

watch(currentPage, () => {
    fetchAlertas();
});

// Resolver una alerta de manera interactiva
const handleResolveAlerta = async (alerta: any) => {
    isSavingId.value = alerta.id;
    try {
        const payload = {
            estacion_id: alerta.estacion_id,
            config_alerta_id: alerta.config_alerta_id,
            valor_actual: Number(alerta.valor_actual),
            estado: 'resuelto',
            resuelto_at: new Date().toISOString()
        };
        await alertaService.actualizarAlerta(alerta.id, payload);
        
        // Recargar
        await fetchAlertas();
    } catch (error) {
        console.error('Error al resolver la alerta:', error);
    } finally {
        isSavingId.value = null;
    }
};

const handleDeleteAlerta = async (id: number) => {
    isLoading.value = true;
    try {
        await alertaService.eliminarAlerta(id);
        if (alertas.value.length === 1 && currentPage.value > 1) {
            currentPage.value--;
        } else {
            await fetchAlertas();
        }
    } catch (error) {
        console.error('Error al eliminar la alerta:', error);
    } finally {
        isLoading.value = false;
    }
};

const getFriendlyMetricName = (metrica: string) => {
    switch (metrica) {
        case 'carga_cpu': return 'Exceso de Carga de CPU';
        case 'uso_ram_mb': return 'Exceso de Uso de RAM';
        case 'temp_cpu': return 'Alta Temperatura CPU';
        case 'uso_disco': return 'Espacio en Disco Crítico';
        case 'latencia_red': return 'Latencia Crítica';
        default: return metrica;
    }
};

const formatThreshold = (metrica: string, value: number) => {
    switch (metrica) {
        case 'carga_cpu': return `${value}%`;
        case 'uso_ram_mb': return value >= 1024 ? `${(value/1024).toFixed(1)} GB` : `${value} MB`;
        case 'temp_cpu': return `${value}°C`;
        case 'uso_disco': return `${value}%`;
        case 'latencia_red': return `${value} ms`;
        default: return value.toString();
    }
};

const getMetricIcon = (metrica: string) => {
    switch (metrica) {
        case 'carga_cpu': return Cpu;
        case 'uso_ram_mb': return Database;
        case 'temp_cpu': return Thermometer;
        case 'uso_disco': return Database;
        case 'latencia_red': return Wifi;
        default: return AlertOctagon;
    }
};

onMounted(() => {
    fetchAlertas();
});
</script>

<template>
    <div class="p-6 space-y-6 relative">
        <!-- Loader Overlay -->
        <div v-if="isLoading"
            class="absolute inset-0 bg-white/40 backdrop-blur-xs z-50 flex items-center justify-center min-h-100 rounded-xl transition-all">
            <div class="bg-white p-4 rounded-xl shadow-lg border border-gray-100 flex items-center gap-3">
                <Loader2 class="w-5 h-5 text-blue-600 animate-spin" />
                <span class="text-sm font-medium text-gray-700">Analizando alertas del sistema...</span>
            </div>
        </div>

        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <AlertOctagon class="w-7 h-7 text-red-600 animate-pulse" />
                    Monitoreo de Alertas
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ totalAlertas }} alarmas reactivas detectadas por anomalías en los sensores de rendimiento de red
                </p>
            </div>
            
            <button @click="fetchAlertas"
                class="inline-flex items-center justify-center bg-white hover:bg-slate-50 text-gray-700 font-medium py-2 px-3 border border-gray-250 rounded-lg shadow-sm gap-1.5 transition-colors text-xs cursor-pointer">
                <RefreshCw class="w-3.5 h-3.5" />
                Actualizar Canal
            </button>
        </div>

        <!-- Filtros -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="relative md:col-span-2">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input v-model="searchTerm" type="text" placeholder="Buscar por estación, métrica o severidad..."
                        class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 text-gray-900 placeholder:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition-all" />
                </div>
                
                <select v-model="filterEstado"
                    class="bg-white border border-gray-200 text-gray-900 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm cursor-pointer">
                    <option value="pendiente">Pendientes de Atención</option>
                    <option value="resuelto">Resueltas</option>
                    <option value="all">Todas las alertas</option>
                </select>
            </div>
        </div>

        <!-- Listado en Cards Premium -->
        <div v-if="alertas.length === 0 && !isLoading" class="bg-white rounded-xl border border-gray-200 p-16 text-center text-gray-400 italic">
            <AlertOctagon class="w-12 h-12 text-slate-200 mx-auto mb-3" />
            <p class="font-medium text-slate-600 text-sm">Monitoreo Saludable</p>
            <p class="text-xs text-gray-400 mt-1 max-w-xs mx-auto">No se registran alarmas pendientes de atención en este momento.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="alerta in alertas" :key="alerta.id"
                class="bg-white border border-gray-200 hover:border-red-300 rounded-xl p-5 hover:shadow-md transition-all flex flex-col justify-between"
                :class="alerta.estado === 'pendiente' ? 'border-l-4 border-l-red-500' : 'border-l-4 border-l-emerald-500'"
            >
                <div class="space-y-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 rounded-lg shrink-0"
                                :class="alerta.estado === 'pendiente' ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600'"
                            >
                                <component :is="getMetricIcon(alerta.configuracion_alerta?.metrica)" class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 text-sm leading-tight">
                                    {{ getFriendlyMetricName(alerta.configuracion_alerta?.metrica) }}
                                </h3>
                                <p class="text-3xs text-gray-400 font-mono tracking-tighter uppercase mt-0.5">
                                    {{ alerta.configuracion_alerta?.metrica }} {{ alerta.configuracion_alerta?.operador }} {{ formatThreshold(alerta.configuracion_alerta?.metrica, alerta.configuracion_alerta?.valor_umbral) }}
                                </p>
                            </div>
                        </div>
                        
                        <span :class="[
                            'text-3xs font-semibold px-2 py-0.5 rounded-full border uppercase shadow-3xs',
                            alerta.configuracion_alerta?.severidad === 'critica' ? 'bg-red-50 text-red-700 border-red-200' :
                            alerta.configuracion_alerta?.severidad === 'alta' ? 'bg-orange-50 text-orange-700 border-orange-200' :
                            alerta.configuracion_alerta?.severidad === 'media' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                            'bg-blue-50 text-blue-700 border-blue-200'
                        ]">
                            {{ alerta.configuracion_alerta?.severidad }}
                        </span>
                    </div>

                    <div class="bg-slate-50 border border-slate-100 rounded-lg p-3 text-xs space-y-1.5 font-medium text-gray-600">
                        <div class="flex items-center justify-between">
                            <span>Estación de Trabajo:</span>
                            <span class="text-gray-950 font-bold font-mono">{{ alerta.estacion?.hostname }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Dirección IP:</span>
                            <span class="text-gray-700 font-mono">{{ alerta.estacion?.direccion_ip }}</span>
                        </div>
                        <div class="flex items-center justify-between pt-1 border-t border-slate-150">
                            <span>Valor Registrado:</span>
                            <span class="text-red-600 font-bold font-mono text-sm">
                                {{ formatThreshold(alerta.configuracion_alerta?.metrica, alerta.valor_actual) }}
                            </span>
                        </div>
                    </div>

                    <div class="text-3xs text-gray-400 font-mono space-y-0.5">
                        <p>DISPARADA: {{ new Date(alerta.created_at).toLocaleString() }}</p>
                        <p v-if="alerta.estado === 'resuelto' && alerta.resuelto_at">
                            RESUELTA: {{ new Date(alerta.resuelto_at).toLocaleString() }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-4 border-t border-gray-100 mt-5">
                    <template v-if="authStore.can('alertas.editar')">
                        <button 
                            v-if="alerta.estado === 'pendiente'"
                            @click="handleResolveAlerta(alerta)"
                            :disabled="isSavingId === alerta.id"
                            class="flex-1 inline-flex items-center justify-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 disabled:opacity-50 text-xs font-semibold rounded-lg text-white shadow-sm transition-colors cursor-pointer gap-1"
                        >
                            <Loader2 v-if="isSavingId === alerta.id" class="w-3.5 h-3.5 animate-spin" />
                            <CheckCircle2 v-else class="w-3.5 h-3.5" />
                            <span>Marcar Resuelta</span>
                        </button>
                        
                        <span 
                            v-else
                            class="flex-1 inline-flex items-center justify-center px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-250 text-xs font-semibold rounded-lg gap-1 uppercase select-none"
                        >
                            <CheckCircle2 class="w-3.5 h-3.5" />
                            <span>Atendida</span>
                        </span>
                    </template>
                    <template v-else>
                        <span 
                            v-if="alerta.estado === 'pendiente'"
                            class="flex-1 inline-flex items-center justify-center px-3 py-1.5 bg-red-50 text-red-700 border border-red-250 text-xs font-semibold rounded-lg gap-1 uppercase select-none"
                        >
                            <AlertOctagon class="w-3.5 h-3.5" />
                            <span>Pendiente</span>
                        </span>
                        <span 
                            v-else
                            class="flex-1 inline-flex items-center justify-center px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-250 text-xs font-semibold rounded-lg gap-1 uppercase select-none"
                        >
                            <CheckCircle2 class="w-3.5 h-3.5" />
                            <span>Atendida</span>
                        </span>
                    </template>

                    <button v-if="authStore.can('alertas.eliminar')" @click="handleDeleteAlerta(alerta.id)"
                        class="inline-flex items-center justify-center p-1.5 border border-gray-250 hover:bg-red-50 hover:border-red-200 hover:text-red-600 rounded-lg text-gray-400 transition-colors cursor-pointer ml-auto">
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Paginador -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <BasePagination v-model="currentPage" :last-page="lastPage" :total="totalAlertas" />
        </div>
    </div>
</template>
